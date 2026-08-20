<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreSupplierRequest;
use App\Http\Requests\Admin\UpdateSupplierRequest;
use App\Models\BankAccount;
use App\Models\Branch;
use App\Models\Company;
use App\Models\Supplier;
use App\Services\SupplierService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class SupplierController extends Controller
{
    public function __construct(protected SupplierService $supplierService) {}

    public function index(Request $request): View
    {
        $filters = $request->only([
            'search', 'supplier_code', 'mobile', 'gstin', 'status', 'supplier_type'
        ]);

        $suppliers = $this->supplierService->getPaginatedSuppliers($filters);
        $metrics = $this->supplierService->getSupplierDashboardMetrics();

        $user = Auth::user();
        $companies = $user && $user->isSuperAdmin()
            ? Company::where('is_active', true)->get()
            : Company::where('id', $user ? $user->company_id : 1)->get();

        return view('admin.suppliers.index', compact('suppliers', 'filters', 'metrics', 'companies'));
    }

    public function create(): View
    {
        $user = Auth::user();
        $companies = $user && $user->isSuperAdmin()
            ? Company::where('is_active', true)->get()
            : Company::where('id', $user ? $user->company_id : 1)->get();

        return view('admin.suppliers.create', compact('companies'));
    }

    public function store(StoreSupplierRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $supplier = $this->supplierService->createSupplier($data);

        return redirect()->route('admin.suppliers.show', $supplier->id)
            ->with('success', "Supplier '{$supplier->supplier_name}' ({$supplier->supplier_code}) created successfully.");
    }

    public function show(Supplier $supplier, Request $request): View
    {
        $user = Auth::user();
        if ($user && !$user->isSuperAdmin() && $supplier->company_id !== $user->company_id) {
            abort(403, 'Unauthorized access to supplier profile.');
        }

        $activeTab = $request->get('tab', 'overview');

        // Load relations
        $supplier->load(['company', 'purchases.branch', 'payments.bankAccount', 'payments.allocations.purchase']);

        // Compute Ledger data
        $ledgerData = $this->supplierService->getSupplierLedger(
            $supplier,
            $request->get('start_date'),
            $request->get('end_date')
        );

        // Supplied Products Catalog
        $suppliedProducts = $this->supplierService->getSupplierProducts($supplier);

        // Outstanding Purchases for manual/auto payment allocation
        $outstandingPurchases = $supplier->purchases()
            ->whereIn('purchase_status', ['confirmed', 'received', 'completed'])
            ->where('due_amount', '>', 0)
            ->orderBy('purchase_date', 'asc')
            ->get();

        // Bank Accounts & Branches for payment modal in show page
        $bankAccounts = BankAccount::where('company_id', $supplier->company_id)
            ->where('is_active', true)->get();
        $branches = Branch::where('company_id', $supplier->company_id)
            ->where('is_active', true)->get();

        return view('admin.suppliers.show', compact(
            'supplier',
            'activeTab',
            'ledgerData',
            'suppliedProducts',
            'outstandingPurchases',
            'bankAccounts',
            'branches'
        ));
    }

    public function edit(Supplier $supplier): View
    {
        $user = Auth::user();
        if ($user && !$user->isSuperAdmin() && $supplier->company_id !== $user->company_id) {
            abort(403, 'Unauthorized access to supplier profile.');
        }

        $companies = $user && $user->isSuperAdmin()
            ? Company::where('is_active', true)->get()
            : Company::where('id', $supplier->company_id)->get();

        return view('admin.suppliers.edit', compact('supplier', 'companies'));
    }

    public function update(UpdateSupplierRequest $request, Supplier $supplier): RedirectResponse
    {
        $user = Auth::user();
        if ($user && !$user->isSuperAdmin() && $supplier->company_id !== $user->company_id) {
            abort(403, 'Unauthorized access to supplier profile.');
        }

        $data = $request->validated();
        $this->supplierService->updateSupplier($supplier, $data);

        return redirect()->route('admin.suppliers.show', $supplier->id)
            ->with('success', "Supplier '{$supplier->supplier_name}' updated successfully.");
    }

    public function destroy(Supplier $supplier): RedirectResponse
    {
        $user = Auth::user();
        if ($user && !$user->isSuperAdmin() && $supplier->company_id !== $user->company_id) {
            abort(403, 'Unauthorized access to supplier profile.');
        }

        $name = $supplier->supplier_name;
        $this->supplierService->deleteSupplier($supplier);

        return redirect()->route('admin.suppliers.index')
            ->with('success', "Supplier '{$name}' deleted successfully.");
    }
}
