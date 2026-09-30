<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Expense;
use App\Models\ExpenseCategory;
use App\Services\ExpenseService;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ExpenseApiController extends Controller
{
    use ApiResponse;

    public function __construct(protected ExpenseService $expenseService) {}

    /**
     * Get active expense categories for mobile dropdowns.
     */
    public function categories(Request $request): JsonResponse
    {
        $categories = ExpenseCategory::where('is_active', true)
            ->orderBy('category_name')
            ->get(['id', 'category_code', 'category_name', 'description']);

        return $this->successResponse($categories, 'Expense categories retrieved');
    }

    /**
     * List expenses for authorized branch / filters.
     */
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        $scopedBranchId = $user->resolveScopedBranchId($request->query('branch_id'));

        $query = Expense::with(['branch', 'category', 'supplier', 'requestedBy', 'approvedBy'])
            ->orderByDesc('id');

        if ($scopedBranchId) {
            $query->where('branch_id', $scopedBranchId);
        }

        if ($request->filled('search')) {
            $search = trim($request->query('search'));
            $query->where(function ($q) use ($search) {
                $q->where('expense_number', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%")
                  ->orWhere('payee_name', 'like', "%{$search}%")
                  ->orWhere('reference_number', 'like', "%{$search}%");
            });
        }

        if ($request->filled('category_id')) {
            $query->where('expense_category_id', $request->query('category_id'));
        }

        if ($request->filled('status')) {
            $query->where('status', $request->query('status'));
        }

        if ($request->filled('payment_status')) {
            $query->where('payment_status', $request->query('payment_status'));
        }

        if ($request->filled('date_from')) {
            $query->whereDate('expense_date', '>=', $request->query('date_from'));
        }

        if ($request->filled('date_to')) {
            $query->whereDate('expense_date', '<=', $request->query('date_to'));
        }

        $expenses = $query->paginate($request->query('per_page', 15));

        return $this->successResponse($expenses, 'Expenses retrieved successfully');
    }

    /**
     * Create a new expense record with optional receipt upload.
     */
    public function store(Request $request): JsonResponse
    {
        $user = $request->user();

        $validated = $request->validate([
            'branch_id' => 'required|exists:branches,id',
            'expense_category_id' => 'required|exists:expense_categories,id',
            'expense_date' => 'nullable|date',
            'amount' => 'required|numeric|min:0.01',
            'tax_amount' => 'nullable|numeric|min:0',
            'payee_name' => 'nullable|string|max:150',
            'supplier_id' => 'nullable|exists:suppliers,id',
            'description' => 'required|string|max:1000',
            'reference_number' => 'nullable|string|max:100',
            'notes' => 'nullable|string|max:1000',
            'auto_submit' => 'nullable|boolean',
            'attachment' => 'nullable|file|mimes:pdf,jpg,jpeg,png,doc,docx,xls,xlsx|max:10240',
        ]);

        if (!$user->canAccessBranch($validated['branch_id'])) {
            return $this->forbiddenResponse('Unauthorized access to create expense for another branch');
        }

        try {
            $attachment = $request->file('attachment');
            $expense = $this->expenseService->createExpense($validated, $user, $attachment);
            return $this->successResponse($expense->load(['branch', 'category', 'requestedBy']), 'Expense logged successfully', 201);
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), 422);
        }
    }

    /**
     * Get single expense details.
     */
    public function show(Request $request, int $id): JsonResponse
    {
        $user = $request->user();
        $expense = Expense::with(['branch', 'category', 'supplier', 'requestedBy', 'approvedBy', 'payments.paidBy', 'attachments.uploader'])->find($id);

        if (!$expense) {
            return $this->notFoundResponse('Expense record not found');
        }

        if (!$user->canAccessBranch($expense->branch_id)) {
            return $this->forbiddenResponse('Unauthorized access to view expense in another branch');
        }

        return $this->successResponse($expense, 'Expense details retrieved');
    }

    /**
     * Submit expense for approval.
     */
    public function submit(Request $request, int $id): JsonResponse
    {
        $user = $request->user();
        $expense = Expense::find($id);

        if (!$expense) {
            return $this->notFoundResponse('Expense record not found');
        }

        if (!$user->canAccessBranch($expense->branch_id)) {
            return $this->forbiddenResponse('Unauthorized access to submit expense in another branch');
        }

        try {
            $submitted = $this->expenseService->submitExpense($expense, $user);
            return $this->successResponse($submitted, 'Expense submitted for approval');
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), 422);
        }
    }

    /**
     * Approve expense (Admin / BM role).
     */
    public function approve(Request $request, int $id): JsonResponse
    {
        $user = $request->user();
        $expense = Expense::find($id);

        if (!$expense) {
            return $this->notFoundResponse('Expense record not found');
        }

        if (!$user->canAccessBranch($expense->branch_id)) {
            return $this->forbiddenResponse('Unauthorized access to approve expense in another branch');
        }

        try {
            $approved = $this->expenseService->approveExpense($expense, $user);
            return $this->successResponse($approved, 'Expense approved successfully');
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), 422);
        }
    }

    /**
     * Reject expense.
     */
    public function reject(Request $request, int $id): JsonResponse
    {
        $user = $request->user();
        $expense = Expense::find($id);

        if (!$expense) {
            return $this->notFoundResponse('Expense record not found');
        }

        if (!$user->canAccessBranch($expense->branch_id)) {
            return $this->forbiddenResponse('Unauthorized access to reject expense in another branch');
        }

        $validated = $request->validate([
            'rejection_reason' => 'required|string|max:500',
        ]);

        try {
            $rejected = $this->expenseService->rejectExpense($expense, $user, $validated['rejection_reason']);
            return $this->successResponse($rejected, 'Expense rejected successfully');
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), 422);
        }
    }

    /**
     * Record payment for approved expense.
     */
    public function pay(Request $request, int $id): JsonResponse
    {
        $user = $request->user();
        $expense = Expense::find($id);

        if (!$expense) {
            return $this->notFoundResponse('Expense record not found');
        }

        if (!$user->canAccessBranch($expense->branch_id)) {
            return $this->forbiddenResponse('Unauthorized access to record payment for expense in another branch');
        }

        $validated = $request->validate([
            'paid_amount' => 'required|numeric|min:0.01',
            'payment_method' => 'required|string|in:cash,bank_transfer,cheque,upi,online',
            'payment_date' => 'nullable|date',
            'bank_account_id' => 'nullable|exists:bank_accounts,id',
            'transaction_reference' => 'nullable|string|max:100',
            'notes' => 'nullable|string|max:1000',
            'attachment' => 'nullable|file|mimes:pdf,jpg,jpeg,png,doc,docx,xls,xlsx|max:10240',
        ]);

        try {
            $attachment = $request->file('attachment');
            $payment = $this->expenseService->recordPayment($expense, $validated, $user, $attachment);
            return $this->successResponse([
                'payment' => $payment,
                'expense' => $expense->fresh(['branch', 'category']),
            ], 'Expense payment recorded and GL voucher created');
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), 422);
        }
    }
}
