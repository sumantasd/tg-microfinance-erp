<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\ProductCategory;
use App\Models\ProductBrand;
use App\Models\Product;
use App\Models\InventoryStock;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class InventoryApiController extends Controller
{
    use ApiResponse;

    /**
     * Get active product categories.
     */
    public function categories(Request $request): JsonResponse
    {
        $categories = ProductCategory::where('is_active', true)->get();

        return $this->successResponse($categories, 'Product categories retrieved');
    }

    /**
     * Get active product brands (optionally filtered by category_id).
     */
    public function brands(Request $request): JsonResponse
    {
        $query = ProductBrand::where('is_active', true);

        if ($categoryId = $request->query('category_id')) {
            $query->whereHas('products', function ($q) use ($categoryId) {
                $q->where('category_id', $categoryId);
            });
        }

        $brands = $query->get();

        return $this->successResponse($brands, 'Product brands retrieved');
    }

    /**
     * Get active products (optionally filtered by category_id or brand_id).
     */
    public function products(Request $request): JsonResponse
    {
        $query = Product::with(['categoryRel', 'brandRel'])->where('is_active', true);

        if ($categoryId = $request->query('category_id')) {
            $query->where('category_id', $categoryId);
        }

        if ($brandId = $request->query('brand_id')) {
            $query->where('brand_id', $brandId);
        }

        if ($search = $request->query('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('sku', 'like', "%{$search}%");
            });
        }

        $products = $query->get();

        return $this->successResponse($products, 'Products retrieved');
    }

    /**
     * Get branch inventory stock levels.
     */
    public function stocks(Request $request): JsonResponse
    {
        $user = $request->user();
        $scopedBranchId = $user->resolveScopedBranchId($request->query('branch_id'));

        $query = InventoryStock::with(['product.categoryRel', 'product.brandRel', 'branch']);

        if ($scopedBranchId) {
            $query->where('branch_id', $scopedBranchId);
        }

        $stocks = $query->get();

        return $this->successResponse($stocks, 'Branch inventory stock levels retrieved');
    }
}
