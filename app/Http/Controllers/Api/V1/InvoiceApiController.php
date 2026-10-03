<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Invoice;
use App\Services\BillingService;
use App\Services\SaleService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class InvoiceApiController extends Controller
{
    public function __construct(
        protected BillingService $billingService,
        protected SaleService $saleService
    ) {}

    public function index(Request $request): JsonResponse
    {
        $user = Auth::user();
        $query = Invoice::with(["branch", "customer", "items.product"])
            ->latest("id");

        if ($user && !$user->isSuperAdmin() && !$user->isCompanyAdmin()) {
            if ($user->branch_id) {
                $query->where("branch_id", $user->branch_id);
            }
        } elseif ($request->filled("branch_id")) {
            $query->where("branch_id", $request->input("branch_id"));
        }

        if ($request->filled("status")) {
            $query->where("status", $request->input("status"));
        }

        if ($request->filled("search")) {
            $search = trim($request->input("search"));
            $query->where(function ($q) use ($search) {
                $q->where("invoice_number", "like", "%{$search}%")
                  ->orWhere("reference_number", "like", "%{$search}%")
                  ->orWhereHas("customer", function ($cq) use ($search) {
                      $cq->where("first_name", "like", "%{$search}%")
                        ->orWhere("last_name", "like", "%{$search}%")
                        ->orWhere("customer_code", "like", "%{$search}%")
                        ->orWhere("phone", "like", "%{$search}%");
                  });
            });
        }

        $invoices = $query->paginate($request->input("per_page", 20));

        return response()->json([
            "success" => true,
            "data" => $invoices->items(),
            "meta" => [
                "current_page" => $invoices->currentPage(),
                "last_page" => $invoices->lastPage(),
                "total" => $invoices->total(),
            ]
        ]);
    }

    public function show($id): JsonResponse
    {
        $user = Auth::user();
        $invoice = Invoice::with(["branch", "customer", "items.product", "creator"])->findOrFail($id);

        if ($user && !$user->isSuperAdmin() && !$user->isCompanyAdmin()) {
            if ($user->branch_id && (int) $invoice->branch_id !== (int) $user->branch_id) {
                return response()->json([
                    "success" => false,
                    "message" => "Unauthorized access to invoice belonging to another branch."
                ], 403);
            }
        }

        return response()->json([
            "success" => true,
            "data" => $invoice,
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $user = Auth::user();
        $branchId = $user?->branch_id ?? $request->input("branch_id");

        $validated = $request->validate([
            "customer_id" => "nullable|exists:customers,id",
            "customer_name" => "required|string|max:255",
            "customer_phone" => "nullable|string|max:20",
            "customer_address" => "nullable|string|max:500",
            "sale_date" => "required|date",
            "payment_method" => "required|string|in:cash,bank_transfer,upi,cheque",
            "paid_amount" => "required|numeric|min:0",
            "remarks" => "nullable|string|max:500",
            "items" => "required|array|min:1",
            "items.*.product_id" => "required|exists:products,id",
            "items.*.quantity" => "required|integer|min:1",
            "items.*.unit_price" => "required|numeric|min:0",
            "items.*.discount" => "nullable|numeric|min:0",
            "items.*.tax" => "nullable|numeric|min:0",
        ]);

        $validated["branch_id"] = $branchId;

        $sale = $this->saleService->createDirectSale($validated);
        $sale->invoice->load(["branch", "customer", "items.product"]);

        return response()->json([
            "success" => true,
            "message" => "Invoice created successfully",
            "data" => $sale->invoice,
        ], 201);
    }
}
