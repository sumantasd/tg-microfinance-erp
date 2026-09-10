<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ApproveExpenseRequest;
use App\Http\Requests\Admin\CancelExpenseRequest;
use App\Http\Requests\Admin\PayExpenseRequest;
use App\Http\Requests\Admin\RejectExpenseRequest;
use App\Http\Requests\Admin\StoreExpenseRequest;
use App\Http\Requests\Admin\UpdateExpenseRequest;
use App\Models\BankAccount;
use App\Models\Branch;
use App\Models\Expense;
use App\Models\ExpenseAttachment;
use App\Models\ExpenseCategory;
use App\Models\Supplier;
use App\Models\User;
use App\Services\ExpenseService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ExpenseController extends Controller
{
    public function __construct(protected ExpenseService $expenseService) {}

    /**
     * Get accessible retail branches for the logged-in user.
     */
    protected function getAccessibleBranches()
    {
        $user = Auth::user();
        $query = Branch::where('is_active', true)->where('is_warehouse', false);

        if ($user && !$user->isSuperAdmin()) {
            $query->where('company_id', $user->company_id);
            if (!$user->isCompanyAdmin() && $user->branch_id) {
                $query->where('id', $user->branch_id);
            }
        }

        return $query->orderBy('name')->get();
    }

    /**
     * Expense Dashboard & Analytics View.
     */
    public function dashboard(Request $request): View
    {
        $user = Auth::user();
        $branches = $this->getAccessibleBranches();
        $branchIds = $branches->pluck('id');

        $query = Expense::whereIn('branch_id', $branchIds);

        // Date range filter
        if ($request->filled('date_from')) {
            $query->whereDate('expense_date', '>=', $request->get('date_from'));
        }
        if ($request->filled('date_to')) {
            $query->whereDate('expense_date', '<=', $request->get('date_to'));
        }
        if ($request->filled('branch_id')) {
            $query->where('branch_id', $request->get('branch_id'));
        }

        // Aggregate statistics directly in DB
        $stats = (clone $query)
            ->selectRaw("
                COUNT(*) as total_count,
                COALESCE(SUM(total_amount), 0) as total_amount,
                COALESCE(SUM(CASE WHEN status != 'CANCELLED' AND status != 'REJECTED' THEN total_amount ELSE 0 END), 0) as active_total_amount,
                COALESCE(SUM(paid_amount), 0) as paid_amount,
                COALESCE(SUM(CASE WHEN status IN ('APPROVED', 'PARTIALLY_PAID') THEN outstanding_amount ELSE 0 END), 0) as outstanding_amount,
                COALESCE(SUM(CASE WHEN status = 'PENDING_APPROVAL' THEN 1 ELSE 0 END), 0) as pending_approval_count,
                COALESCE(SUM(CASE WHEN status = 'APPROVED' THEN 1 ELSE 0 END), 0) as approved_count,
                COALESCE(SUM(CASE WHEN status = 'PAID' THEN 1 ELSE 0 END), 0) as paid_count,
                COALESCE(SUM(CASE WHEN status = 'CANCELLED' THEN 1 ELSE 0 END), 0) as cancelled_count
            ")->first();

        // This Month vs This Year stats
        $thisMonthStats = Expense::whereIn('branch_id', $branchIds)
            ->whereYear('expense_date', date('Y'))
            ->whereMonth('expense_date', date('m'))
            ->whereNotIn('status', ['CANCELLED', 'REJECTED'])
            ->sum('total_amount');

        $thisYearStats = Expense::whereIn('branch_id', $branchIds)
            ->whereYear('expense_date', date('Y'))
            ->whereNotIn('status', ['CANCELLED', 'REJECTED'])
            ->sum('total_amount');

        // Expense by Category Breakdown
        $categoryBreakdown = Expense::whereIn('branch_id', $branchIds)
            ->whereNotIn('status', ['CANCELLED', 'REJECTED'])
            ->selectRaw('expense_category_id, SUM(total_amount) as total_sum, COUNT(*) as count')
            ->groupBy('expense_category_id')
            ->with('category')
            ->orderByDesc('total_sum')
            ->get();

        // Expense by Branch Breakdown
        $branchBreakdown = Expense::whereIn('branch_id', $branchIds)
            ->whereNotIn('status', ['CANCELLED', 'REJECTED'])
            ->selectRaw('branch_id, SUM(total_amount) as total_sum, COUNT(*) as count')
            ->groupBy('branch_id')
            ->with('branch')
            ->orderByDesc('total_sum')
            ->get();

        // Recent 5 Pending Approvals
        $recentPending = Expense::whereIn('branch_id', $branchIds)
            ->where('status', Expense::STATUS_PENDING_APPROVAL)
            ->with(['branch', 'category', 'requestedBy'])
            ->orderByDesc('id')
            ->limit(5)
            ->get();

        return view('admin.expenses.dashboard', compact(
            'stats',
            'thisMonthStats',
            'thisYearStats',
            'categoryBreakdown',
            'branchBreakdown',
            'recentPending',
            'branches'
        ));
    }

    /**
     * Expense Listing Index View.
     */
    public function index(Request $request): View
    {
        $user = Auth::user();
        $branches = $this->getAccessibleBranches();
        $branchIds = $branches->pluck('id');

        $query = Expense::whereIn('branch_id', $branchIds)
            ->with(['branch', 'category', 'supplier', 'requestedBy', 'approvedBy']);

        // Filters
        if ($request->filled('search')) {
            $search = trim($request->get('search'));
            $query->where(function ($q) use ($search) {
                $q->where('expense_number', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%")
                  ->orWhere('payee_name', 'like', "%{$search}%")
                  ->orWhere('reference_number', 'like', "%{$search}%");
            });
        }

        if ($request->filled('branch_id')) {
            $query->where('branch_id', $request->get('branch_id'));
        }

        if ($request->filled('category_id')) {
            $query->where('expense_category_id', $request->get('category_id'));
        }

        if ($request->filled('status')) {
            $query->where('status', $request->get('status'));
        }

        if ($request->filled('payment_status')) {
            $query->where('payment_status', $request->get('payment_status'));
        }

        if ($request->filled('date_from')) {
            $query->whereDate('expense_date', '>=', $request->get('date_from'));
        }

        if ($request->filled('date_to')) {
            $query->whereDate('expense_date', '<=', $request->get('date_to'));
        }

        $expenses = $query->orderByDesc('id')->paginate(15)->withQueryString();

        $categories = ExpenseCategory::where('is_active', true)->orderBy('category_name')->get();
        $suppliers = Supplier::where('status', 'active')->orderBy('supplier_name')->get();

        return view('admin.expenses.index', compact('expenses', 'branches', 'categories', 'suppliers'));
    }

    /**
     * Expense Create Form.
     */
    public function create(): View
    {
        $branches = $this->getAccessibleBranches();
        $categories = ExpenseCategory::where('is_active', true)->orderBy('category_name')->get();
        $suppliers = Supplier::where('status', 'active')->orderBy('supplier_name')->get();
        $staffUsers = User::orderBy('name')->get();

        return view('admin.expenses.create', compact('branches', 'categories', 'suppliers', 'staffUsers'));
    }

    /**
     * Store Expense.
     */
    public function store(StoreExpenseRequest $request): RedirectResponse
    {
        $user = Auth::user();
        $data = $request->validated();
        $attachment = $request->file('attachment');

        $expense = $this->expenseService->createExpense($data, $user, $attachment);

        return redirect()->route('admin.expenses.show', $expense->id)
            ->with('success', "Expense '{$expense->expense_number}' created successfully.");
    }

    /**
     * View Expense Details.
     */
    public function show(Expense $expense): View|RedirectResponse
    {
        $user = Auth::user();
        $branches = $this->getAccessibleBranches();
        if (!$branches->contains('id', $expense->branch_id) && !$user->isSuperAdmin()) {
            return redirect()->route('admin.expenses.index')
                ->with('error', 'You are not authorized to view expenses for this branch.');
        }

        $expense->load(['branch', 'category', 'supplier', 'requestedBy', 'approvedBy', 'payments.bankAccount', 'payments.paidBy', 'attachments.uploader']);

        $bankAccounts = BankAccount::where('company_id', $expense->company_id)
            ->where('is_active', true)
            ->get();

        return view('admin.expenses.show', compact('expense', 'bankAccounts'));
    }

    /**
     * Expense Edit Form.
     */
    public function edit(Expense $expense): View|RedirectResponse
    {
        $user = Auth::user();
        if (!$expense->isEditable()) {
            return redirect()->route('admin.expenses.show', $expense->id)
                ->with('error', 'Only DRAFT or REJECTED expenses can be edited.');
        }

        $branches = $this->getAccessibleBranches();
        $categories = ExpenseCategory::where('is_active', true)->orderBy('category_name')->get();
        $suppliers = Supplier::where('status', 'active')->orderBy('supplier_name')->get();
        $staffUsers = User::orderBy('name')->get();

        return view('admin.expenses.edit', compact('expense', 'branches', 'categories', 'suppliers', 'staffUsers'));
    }

    /**
     * Update Expense.
     */
    public function update(UpdateExpenseRequest $request, Expense $expense): RedirectResponse
    {
        $user = Auth::user();
        $data = $request->validated();
        $attachment = $request->file('attachment');

        $this->expenseService->updateExpense($expense, $data, $user, $attachment);

        return redirect()->route('admin.expenses.show', $expense->id)
            ->with('success', "Expense '{$expense->expense_number}' updated successfully.");
    }

    /**
     * Submit Expense for Approval.
     */
    public function submit(Expense $expense): RedirectResponse
    {
        $user = Auth::user();
        $this->expenseService->submitExpense($expense, $user);

        return redirect()->back()
            ->with('success', "Expense '{$expense->expense_number}' submitted for approval.");
    }

    /**
     * Approve Expense.
     */
    public function approve(ApproveExpenseRequest $request, Expense $expense): RedirectResponse
    {
        $user = Auth::user();
        $this->expenseService->approveExpense($expense, $user);

        return redirect()->back()
            ->with('success', "Expense '{$expense->expense_number}' approved successfully.");
    }

    /**
     * Reject Expense.
     */
    public function reject(RejectExpenseRequest $request, Expense $expense): RedirectResponse
    {
        $user = Auth::user();
        $reason = $request->validated()['rejection_reason'];

        $this->expenseService->rejectExpense($expense, $user, $reason);

        return redirect()->back()
            ->with('success', "Expense '{$expense->expense_number}' rejected.");
    }

    /**
     * Record Expense Payment.
     */
    public function pay(PayExpenseRequest $request, Expense $expense): RedirectResponse
    {
        $user = Auth::user();
        $paymentData = $request->validated();
        $attachment = $request->file('attachment');

        $payment = $this->expenseService->recordPayment($expense, $paymentData, $user, $attachment);

        return redirect()->back()
            ->with('success', "Payment of ₹" . number_format($payment->paid_amount, 2) . " recorded successfully. Payment Voucher created.");
    }

    /**
     * Cancel Expense.
     */
    public function cancel(CancelExpenseRequest $request, Expense $expense): RedirectResponse
    {
        $user = Auth::user();
        $reason = $request->validated()['cancellation_reason'];

        $this->expenseService->cancelExpense($expense, $user, $reason);

        return redirect()->back()
            ->with('success', "Expense '{$expense->expense_number}' cancelled and accounting entries reversed.");
    }

    /**
     * Delete Expense.
     */
    public function destroy(Expense $expense): RedirectResponse
    {
        $user = Auth::user();
        $this->expenseService->deleteExpense($expense, $user);

        return redirect()->route('admin.expenses.index')
            ->with('success', "Expense '{$expense->expense_number}' deleted successfully.");
    }

    /**
     * Download Attachment File safely.
     */
    public function downloadAttachment(ExpenseAttachment $attachment): StreamedResponse|RedirectResponse
    {
        $user = Auth::user();
        $expense = $attachment->expense;

        if (!$expense || (!$user->isSuperAdmin() && !$user->canAccessBranch($expense->branch_id))) {
            return redirect()->route('admin.expenses.index')
                ->with('error', 'Unauthorized attachment access.');
        }

        if (!Storage::disk('private')->exists($attachment->file_path) && !Storage::exists($attachment->file_path)) {
            return redirect()->back()->with('error', 'File not found on server.');
        }

        $disk = Storage::disk('private')->exists($attachment->file_path) ? 'private' : null;

        return Storage::disk($disk)->download($attachment->file_path, $attachment->file_name);
    }
}
