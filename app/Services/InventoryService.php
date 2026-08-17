<?php

namespace App\Services;

use App\Models\InventoryStock;
use App\Models\InventoryStockMovement;
use App\Models\Product;
use App\Repositories\InventoryRepositoryInterface;
use App\Repositories\ProductRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class InventoryService
{
    public function __construct(
        protected ProductRepositoryInterface $productRepository,
        protected InventoryRepositoryInterface $inventoryRepository,
        protected ActivityLogService $activityLogService
    ) {}

    // --- Product Catalog Methods ---

    public function getPaginatedProducts(array $filters = [], int $perPage = 10): LengthAwarePaginator
    {
        $user = Auth::user();
        if ($user && !$user->isSuperAdmin()) {
            $filters['company_id'] = $user->company_id;
        }

        return $this->productRepository->getPaginatedProducts($filters, $perPage);
    }

    public function getProductById(int $id): ?Product
    {
        return $this->productRepository->findById($id);
    }

    public function createProduct(array $data): Product
    {
        return DB::transaction(function () use ($data) {
            $user = Auth::user();
            if ($user && !$user->isSuperAdmin()) {
                $data['company_id'] = $user->company_id;
            } elseif (empty($data['company_id'])) {
                $data['company_id'] = \App\Models\Company::first()->id ?? 1;
            }

            if (!empty($data['brand_id'])) {
                $brand = \App\Models\ProductBrand::find($data['brand_id']);
                if ($brand) {
                    $data['brand'] = $brand->name;
                }
            } elseif (!empty($data['brand'])) {
                $brand = \App\Models\ProductBrand::where('company_id', $data['company_id'])->where('name', $data['brand'])->first();
                if ($brand) {
                    $data['brand_id'] = $brand->id;
                }
            }

            if (!empty($data['category_id'])) {
                $cat = \App\Models\ProductCategory::find($data['category_id']);
                if ($cat) {
                    $data['category'] = $cat->name;
                }
            } elseif (!empty($data['category'])) {
                $cat = \App\Models\ProductCategory::where('company_id', $data['company_id'])->where('name', $data['category'])->first();
                if ($cat) {
                    $data['category_id'] = $cat->id;
                }
            }

            if (empty($data['sku'])) {
                $data['sku'] = $this->productRepository->generateSku($data['company_id']);
            }

            $data['created_by'] = Auth::id();
            $data['updated_by'] = Auth::id();
            $data['is_active'] = isset($data['is_active']) ? (bool) $data['is_active'] : true;

            $product = $this->productRepository->createProduct($data);
            $this->activityLogService->log('product_created', $product);

            return $product;
        });
    }

    public function updateProduct(Product $product, array $data): Product
    {
        return DB::transaction(function () use ($product, $data) {
            $companyId = $data['company_id'] ?? $product->company_id;

            if (array_key_exists('brand_id', $data)) {
                if (!empty($data['brand_id'])) {
                    $brand = \App\Models\ProductBrand::find($data['brand_id']);
                    if ($brand) {
                        $data['brand'] = $brand->name;
                    }
                } else {
                    $data['brand_id'] = null;
                }
            } elseif (!empty($data['brand'])) {
                $brand = \App\Models\ProductBrand::where('company_id', $companyId)->where('name', $data['brand'])->first();
                if ($brand) {
                    $data['brand_id'] = $brand->id;
                }
            }

            if (array_key_exists('category_id', $data)) {
                if (!empty($data['category_id'])) {
                    $cat = \App\Models\ProductCategory::find($data['category_id']);
                    if ($cat) {
                        $data['category'] = $cat->name;
                    }
                } else {
                    $data['category_id'] = null;
                }
            } elseif (!empty($data['category'])) {
                $cat = \App\Models\ProductCategory::where('company_id', $companyId)->where('name', $data['category'])->first();
                if ($cat) {
                    $data['category_id'] = $cat->id;
                }
            }

            $data['updated_by'] = Auth::id();
            if (isset($data['is_active'])) {
                $data['is_active'] = (bool) $data['is_active'];
            }

            $updatedProduct = $this->productRepository->updateProduct($product, $data);
            $this->activityLogService->log('product_updated', $updatedProduct);

            return $updatedProduct;
        });
    }

    public function deleteProduct(Product $product): bool
    {
        return DB::transaction(function () use ($product) {
            $this->activityLogService->log('product_deleted', $product);
            return $this->productRepository->deleteProduct($product);
        });
    }

    // --- Inventory Stock & Generic Ledger Movement Methods ---

    public function getPaginatedStock(array $filters = [], int $perPage = 10): LengthAwarePaginator
    {
        $user = Auth::user();
        if ($user && !$user->isSuperAdmin()) {
            $filters['company_id'] = $user->company_id;
            if ($user->branch_id && !$user->isCompanyAdmin() && empty($filters['branch_id'])) {
                $filters['branch_id'] = $user->branch_id;
            }
        }

        return $this->inventoryRepository->getPaginatedStock($filters, $perPage);
    }

    public function getPaginatedMovements(array $filters = [], int $perPage = 10): LengthAwarePaginator
    {
        $user = Auth::user();
        if ($user && !$user->isSuperAdmin()) {
            $filters['company_id'] = $user->company_id;
            if ($user->branch_id && !$user->isCompanyAdmin() && empty($filters['branch_id'])) {
                $filters['branch_id'] = $user->branch_id;
            }
        }

        return $this->inventoryRepository->getPaginatedMovements($filters, $perPage);
    }

    /**
     * Restock Product Stock at a Branch (Purchase In / Restock)
     */
    public function restockBranchProduct(int $branchId, int $productId, int $quantity, ?float $unitPrice = null, ?string $remarks = null): InventoryStockMovement
    {
        if ($quantity <= 0) {
            throw ValidationException::withMessages(['quantity' => 'Restock quantity must be greater than zero.']);
        }

        return DB::transaction(function () use ($branchId, $productId, $quantity, $unitPrice, $remarks) {
            $product = Product::findOrFail($productId);
            $companyId = $product->company_id;
            $unitPrice = $unitPrice ?? (float) $product->unit_price;

            // Row-level lock on inventory_stocks
            $stock = DB::table('inventory_stocks')
                ->where('branch_id', $branchId)
                ->where('product_id', $productId)
                ->lockForUpdate()
                ->first();

            $stockBefore = $stock ? $stock->current_stock : 0;
            $stockAfter = $stockBefore + $quantity;

            if ($stock) {
                DB::table('inventory_stocks')
                    ->where('id', $stock->id)
                    ->update([
                        'current_stock' => $stockAfter,
                        'last_restocked_at' => now(),
                        'updated_at' => now(),
                    ]);
            } else {
                DB::table('inventory_stocks')->insert([
                    'company_id' => $companyId,
                    'branch_id' => $branchId,
                    'product_id' => $productId,
                    'current_stock' => $stockAfter,
                    'reserved_stock' => 0,
                    'reorder_level' => 5,
                    'last_restocked_at' => now(),
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            $movementCode = $this->inventoryRepository->generateMovementCode($branchId);
            $totalValue = round($quantity * $unitPrice, 2);

            $movement = $this->inventoryRepository->recordStockMovement([
                'company_id' => $companyId,
                'branch_id' => $branchId,
                'product_id' => $productId,
                'movement_code' => $movementCode,
                'movement_type' => 'purchase_in',
                'quantity' => $quantity,
                'unit_price' => $unitPrice,
                'total_value' => $totalValue,
                'stock_before' => $stockBefore,
                'stock_after' => $stockAfter,
                'reference_type' => 'purchase',
                'remarks' => $remarks ?? "Restocked {$quantity} units of {$product->name}.",
                'created_by' => Auth::id(),
            ]);

            $this->activityLogService->log('inventory_restocked', $movement);

            return $movement;
        });
    }

    /**
     * Adjust Branch Stock (Inventory Adjustment)
     */
    public function adjustBranchStock(int $branchId, int $productId, int $newStockLevel, string $remarks): InventoryStockMovement
    {
        if ($newStockLevel < 0) {
            throw ValidationException::withMessages(['new_stock_level' => 'Stock level cannot be negative.']);
        }

        return DB::transaction(function () use ($branchId, $productId, $newStockLevel, $remarks) {
            $product = Product::findOrFail($productId);
            $companyId = $product->company_id;

            $stock = DB::table('inventory_stocks')
                ->where('branch_id', $branchId)
                ->where('product_id', $productId)
                ->lockForUpdate()
                ->first();

            $stockBefore = $stock ? $stock->current_stock : 0;
            $quantityDiff = $newStockLevel - $stockBefore;
            $stockAfter = $newStockLevel;

            if ($stock) {
                DB::table('inventory_stocks')
                    ->where('id', $stock->id)
                    ->update([
                        'current_stock' => $stockAfter,
                        'updated_at' => now(),
                    ]);
            } else {
                DB::table('inventory_stocks')->insert([
                    'company_id' => $companyId,
                    'branch_id' => $branchId,
                    'product_id' => $productId,
                    'current_stock' => $stockAfter,
                    'reserved_stock' => 0,
                    'reorder_level' => 5,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            $movementCode = $this->inventoryRepository->generateMovementCode($branchId);
            $unitPrice = (float) $product->unit_price;

            $movement = $this->inventoryRepository->recordStockMovement([
                'company_id' => $companyId,
                'branch_id' => $branchId,
                'product_id' => $productId,
                'movement_code' => $movementCode,
                'movement_type' => 'adjustment',
                'quantity' => $quantityDiff,
                'unit_price' => $unitPrice,
                'total_value' => round(abs($quantityDiff) * $unitPrice, 2),
                'stock_before' => $stockBefore,
                'stock_after' => $stockAfter,
                'reference_type' => 'adjustment',
                'remarks' => $remarks,
                'created_by' => Auth::id(),
            ]);

            $this->activityLogService->log('inventory_adjusted', $movement);

            return $movement;
        });
    }
}
