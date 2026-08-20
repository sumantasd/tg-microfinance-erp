<?php

namespace App\Repositories;

use App\Models\Supplier;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

class SupplierRepository implements SupplierRepositoryInterface
{
    public function getPaginatedSuppliers(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $query = Supplier::query()->with(['company']);

        if (!empty($filters['company_id'])) {
            $query->where('company_id', $filters['company_id']);
        }

        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (!empty($filters['supplier_type'])) {
            $query->where('supplier_type', $filters['supplier_type']);
        }

        if (!empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('supplier_name', 'like', "%{$search}%")
                  ->orWhere('company_name', 'like', "%{$search}%")
                  ->orWhere('supplier_code', 'like', "%{$search}%")
                  ->orWhere('mobile', 'like', "%{$search}%")
                  ->orWhere('contact_person', 'like', "%{$search}%")
                  ->orWhere('gstin', 'like', "%{$search}%");
            });
        }

        if (!empty($filters['supplier_code'])) {
            $query->where('supplier_code', 'like', "%{$filters['supplier_code']}%");
        }

        if (!empty($filters['mobile'])) {
            $query->where('mobile', 'like', "%{$filters['mobile']}%");
        }

        if (!empty($filters['gstin'])) {
            $query->where('gstin', 'like', "%{$filters['gstin']}%");
        }

        $sortField = $filters['sort_by'] ?? 'id';
        $sortOrder = $filters['sort_order'] ?? 'desc';

        return $query->orderBy($sortField, $sortOrder)->paginate($perPage)->withQueryString();
    }

    public function getAllActiveSuppliers(int $companyId): Collection
    {
        return Supplier::where('company_id', $companyId)
            ->where('status', 'active')
            ->orderBy('supplier_name', 'asc')
            ->get();
    }

    public function findById(int $id): ?Supplier
    {
        return Supplier::with(['company', 'purchases', 'payments.bankAccount'])->find($id);
    }

    public function createSupplier(array $data): Supplier
    {
        return Supplier::create($data);
    }

    public function updateSupplier(Supplier $supplier, array $data): Supplier
    {
        $supplier->update($data);
        return $supplier->fresh();
    }

    public function deleteSupplier(Supplier $supplier): bool
    {
        return $supplier->delete();
    }

    public function generateSupplierCode(int $companyId): string
    {
        $prefix = 'SUP-' . date('Y') . '-';
        $latest = Supplier::where('company_id', $companyId)
            ->where('supplier_code', 'like', $prefix . '%')
            ->withTrashed()
            ->orderBy('id', 'desc')
            ->first();

        if ($latest) {
            $sequenceStr = str_replace($prefix, '', $latest->supplier_code);
            $sequence = (int) $sequenceStr + 1;
        } else {
            $sequence = 1;
        }

        return $prefix . str_pad((string) $sequence, 5, '0', STR_PAD_LEFT);
    }
}
