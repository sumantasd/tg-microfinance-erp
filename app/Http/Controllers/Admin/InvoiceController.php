<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\Customer;
use App\Models\Invoice;
use App\Models\Product;
use App\Services\BillingService;
use App\Services\SaleService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class InvoiceController extends Controller
{
    public function __construct(
        protected BillingService $billingService,
        protected SaleService $saleService
    ) {}

    /**
     * Display Invoice Listing with filters and Branch Isolation.
     */
    public function index(Request $request)
    {
        $this->authorize('billing.view');

        $user = Auth::user();
        $query = Invoice::with(['company', 'branch', 'customer', 'creator'])
            ->latest('id');

        // Branch Isolation Rule: Branch Manager can only see their own branch invoices
        if ($user && !$user->isSuperAdmin() && !$user->isCompanyAdmin()) {
            if ($user->branch_id) {
                $query->where('branch_id', $user->branch_id);
            }
        } elseif ($request->filled('branch_id')) {
            $query->where('branch_id', $request->input('branch_id'));
        }

        if ($request->filled('invoice_type')) {
            $query->where('invoice_type', $request->input('invoice_type'));
        }

        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        if ($request->filled('search')) {
            $search = trim($request->input('search'));
            $query->where(function ($q) use ($search) {
                $q->where('invoice_number', 'like', "%{$search}%")
                  ->orWhere('reference_number', 'like', "%{$search}%")
                  ->orWhereHas('customer', function ($cq) use ($search) {
                      $cq->where('first_name', 'like', "%{$search}%")
                        ->orWhere('last_name', 'like', "%{$search}%")
                        ->orWhere('customer_code', 'like', "%{$search}%")
                        ->orWhere('phone', 'like', "%{$search}%");
                  });
            });
        }

        if ($request->filled('date_from')) {
            $query->whereDate('invoice_date', '>=', $request->input('date_from'));
        }

        if ($request->filled('date_to')) {
            $query->whereDate('invoice_date', '<=', $request->input('date_to'));
        }

        $invoices = $query->paginate(15)->withQueryString();
        $branches = Branch::where('is_active', true)->get();

        return view('admin.billing.index', compact('invoices', 'branches'));
    }

    /**
     * Show Invoice Detail View.
     */
    public function show(Invoice $invoice)
    {
        $this->authorize('billing.view');
        $this->checkBranchAccess($invoice);

        $invoice->load(['company', 'branch', 'customer', 'items.product', 'creator', 'invoiceable']);
        $branding = \App\Services\SystemBrandingService::getBranding();

        return view('admin.billing.show', compact('invoice', 'branding'));
    }

    /**
     * Display A4 Printable View.
     */
    public function print(Invoice $invoice)
    {
        $this->authorize('billing.print');
        $this->checkBranchAccess($invoice);

        $invoice->load(['company', 'branch', 'customer', 'items.product', 'creator', 'invoiceable']);
        $branding = \App\Services\SystemBrandingService::getBranding();

        return view('admin.billing.print', compact('invoice', 'branding'));
    }

    /**
     * Download or view PDF version.
     */
    public function pdf(Invoice $invoice)
    {
        $this->authorize('billing.pdf');
        $this->checkBranchAccess($invoice);

        $invoice->load(['company', 'branch', 'customer', 'items.product', 'creator', 'invoiceable']);
        $branding = \App\Services\SystemBrandingService::getBranding();

        return view('admin.billing.print', compact('invoice', 'branding'));
    }

    /**
     * Direct Product Sale POS / Form Page.
     */
    public function createSale(Request $request)
    {
        $this->authorize('billing.create');

        $user = Auth::user();
        if ($user && !$user->isSuperAdmin() && !$user->isCompanyAdmin() && $user->branch_id) {
            $branches = Branch::where('id', $user->branch_id)->where('is_active', true)->get();
            $selectedBranchId = (int) $user->branch_id;
        } else {
            $branches = Branch::where('is_active', true)->get();
            $selectedBranchId = (int) $request->input('branch_id', $user?->branch_id ?? $branches->first()?->id);
        }

        $customerQuery = Customer::where('status', 'active')->orderBy('first_name');
        if ($user && !$user->isSuperAdmin() && !$user->isCompanyAdmin() && $user->branch_id) {
            $customerQuery->where('branch_id', $user->branch_id);
        }
        $customers = $customerQuery->get();

        $products = Product::where('is_active', true)
            ->with(['stocks' => function ($q) use ($selectedBranchId) {
                $q->where('branch_id', $selectedBranchId);
            }])
            ->get();

        return view('admin.billing.sales.create', compact('branches', 'customers', 'products', 'selectedBranchId'));
    }

    /**
     * Store Direct Product Sale.
     */
    public function storeSale(Request $request)
    {
        $this->authorize('billing.create');

        $validated = $request->validate([
            'branch_id' => 'required|exists:branches,id',
            'customer_id' => 'nullable|exists:customers,id',
            'customer_name' => 'required|string|max:255',
            'customer_phone' => 'nullable|string|max:20',
            'customer_address' => 'nullable|string|max:500',
            'sale_date' => 'required|date',
            'payment_method' => 'required|string|in:cash,bank_transfer,upi,cheque',
            'paid_amount' => 'required|numeric|min:0',
            'remarks' => 'nullable|string|max:500',
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.quantity' => 'required|integer|min:1',
            'items.*.unit_price' => 'required|numeric|min:0',
            'items.*.discount' => 'nullable|numeric|min:0',
            'items.*.tax' => 'nullable|numeric|min:0',
        ]);

        $sale = $this->saleService->createDirectSale($validated);

        return redirect()->route('admin.billing.invoices.show', $sale->invoice->id)
            ->with('success', "Direct Product Sale #{$sale->sale_number} completed and Invoice #{$sale->invoice->invoice_number} generated successfully.");
    }

    /**
     * Cancel an Invoice.
     */
    public function cancel(Request $request, Invoice $invoice)
    {
        $this->authorize('billing.cancel');
        $this->checkBranchAccess($invoice);

        $request->validate([
            'cancellation_reason' => 'required|string|max:500',
        ]);

        $this->billingService->cancelInvoice($invoice, $request->input('cancellation_reason'), Auth::user());

        return redirect()->back()->with('success', "Invoice #{$invoice->invoice_number} has been cancelled.");
    }

    /**
     * Enforce Branch Isolation security rule.
     */
    protected function checkBranchAccess(Invoice $invoice): void
    {
        $user = Auth::user();
        if ($user && !$user->isSuperAdmin() && !$user->isCompanyAdmin()) {
            if ($user->branch_id && (int) $invoice->branch_id !== (int) $user->branch_id) {
                abort(403, 'Unauthorized access to invoice belonging to another branch.');
            }
        }
    }
}
