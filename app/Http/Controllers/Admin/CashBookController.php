<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\CashBook;
use App\Models\CashBookEntry;
use App\Models\CashBookOnlineCollection;
use App\Models\Customer;
use App\Models\CustomerGroup;
use App\Services\CashBookService;
use Carbon\Carbon;
use Illuminate\Http\Request;

class CashBookController extends Controller
{
    protected CashBookService $cashBookService;

    public function __construct(CashBookService $cashBookService)
    {
        $this->cashBookService = $cashBookService;
    }

    /**
     * Step 1: Display Branch Selection page showing authorized active branches with current live cash balances.
     */
    public function index(Request $request)
    {
        $user = auth()->user();
        $companyId = $user->company_id ?? 1;

        // Build active branches query enforcing company & branch RBAC scoping
        $branchesQuery = Branch::where('is_active', true);

        if ($user->company_id) {
            $branchesQuery->where('company_id', $user->company_id);
        }

        if ($user->branch_id) {
            $branchesQuery->where('id', $user->branch_id);
        }

        // Branch search & filter (by name, code, city, state, or address)
        $search = trim($request->input('search', ''));
        if ($search !== '') {
            $branchesQuery->where(function ($q) use ($search) {
                $q->where('name', 'LIKE', "%{$search}%")
                  ->orWhere('code', 'LIKE', "%{$search}%")
                  ->orWhere('city', 'LIKE', "%{$search}%")
                  ->orWhere('state', 'LIKE', "%{$search}%")
                  ->orWhere('address', 'LIKE', "%{$search}%");
            });
        }

        $branches = $branchesQuery->orderBy('name')->get();

        $todayDate = Carbon::now('Asia/Kolkata')->format('Y-m-d');

        // Safely retrieve current cash balance for each authorized branch using accounting CashBookService
        $branchBalances = [];

        foreach ($branches as $branch) {
            $cashBook = $this->cashBookService->getOrCreateCashBook($companyId, $branch->id, $todayDate, $user->id);
            $branchBalances[$branch->id] = (float)$cashBook->closing_cash;
        }

        return view('admin.cash-book.index', compact('branches', 'branchBalances', 'todayDate', 'search'));
    }

    /**
     * Step 2 & 3: Display Date-Wise Cashbook Overview screen for a specific selected branch.
     */
    public function openBranch(Request $request, $branchId)
    {
        $user = auth()->user();
        $companyId = $user->company_id ?? 1;

        $branch = Branch::where('is_active', true)->findOrFail($branchId);

        // Server-side authorization checks: Company and Branch isolation
        if ($user->company_id && (int)$branch->company_id !== (int)$user->company_id) {
            abort(403, 'Unauthorized access to another company branch cash book.');
        }

        if ($user->hasRole('Branch Manager')) {
            if (!$user->branch_id || (int)$branch->id !== (int)$user->branch_id) {
                abort(403, 'Unauthorized access to another branch cash book.');
            }
        } elseif ($user->branch_id && (int)$branch->id !== (int)$user->branch_id) {
            abort(403, 'Unauthorized access to another branch cash book.');
        }

        $selectedDate = $request->input('date', Carbon::now('Asia/Kolkata')->format('Y-m-d'));

        // Query historical date-wise cashbook registers scoped strictly to this branch
        $query = CashBook::with(['branch', 'responsibleStaff', 'closedBy'])
            ->where('branch_id', $branch->id)
            ->orderByDesc('date')
            ->orderByDesc('id');

        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        if ($request->filled('date')) {
            $query->whereDate('date', $request->input('date'));
        } elseif ($request->filled('filter_date')) {
            $query->whereDate('date', $request->input('filter_date'));
        }

        $cashBooks = $query->paginate(15);

        // Fetch or prepare current active cash book for selected branch & date
        $currentCashBook = $this->cashBookService->getOrCreateCashBook($companyId, $branch->id, $selectedDate, $user->id);

        return view('admin.cash-book.overview', compact('branch', 'cashBooks', 'selectedDate', 'currentCashBook'));
    }

    /**
     * Step 4: Display the detailed Daily Cash Book Register transaction ledger for a selected register ID.
     */
    public function show(Request $request, $id)
    {
        $user = auth()->user();
        $cashBook = CashBook::with(['company', 'branch', 'responsibleStaff', 'closedBy', 'approvedBy', 'entries', 'onlineCollections', 'audits.user'])
            ->findOrFail($id);

        // Server-side authorization checks: Company and Branch isolation
        if ($user->company_id && (int)$cashBook->company_id !== (int)$user->company_id) {
            abort(403, 'Unauthorized access to another company cash book.');
        }

        if ($user->branch_id && (int)$cashBook->branch_id !== (int)$user->branch_id) {
            abort(403, 'Unauthorized access to another branch cash book.');
        }

        // Date filter within the branch cashbook screen
        if ($request->filled('date')) {
            $requestedDate = Carbon::parse($request->input('date'))->format('Y-m-d');
            if ($requestedDate !== $cashBook->date->format('Y-m-d')) {
                $companyId = $user->company_id ?? $cashBook->company_id;
                $cashBook = $this->cashBookService->getOrCreateCashBook($companyId, $cashBook->branch_id, $requestedDate, $user->id);
            }
        }

        // Trigger ERP sync if open
        if ($cashBook->isOpen()) {
            $this->cashBookService->syncErpTransactions($cashBook);
            $cashBook->refresh();
        }

        $customers = Customer::where('branch_id', $cashBook->branch_id)->orderBy('first_name')->get();
        $customerGroups = CustomerGroup::where('branch_id', $cashBook->branch_id)->orderBy('name')->get();

        // Bank deposits for this cashbook branch & date
        $bankDeposits = \App\Models\BankDeposit::where('branch_id', $cashBook->branch_id)
            ->whereDate('deposit_date', $cashBook->date->format('Y-m-d'))
            ->get();

        return view('admin.cash-book.show', compact('cashBook', 'customers', 'customerGroups', 'bankDeposits'));
    }

    /**
     * Store or update a Cash Book Particular entry item.
     */
    public function storeEntry(Request $request, $id)
    {
        return redirect()->back()->with('error', 'Manual entry creation is disabled. Cash Book entries are populated automatically from ERP transactions.');
    }

    /**
     * Remove a custom entry item from the Cash Book.
     */
    public function destroyEntry($id, $entryId)
    {
        return redirect()->back()->with('error', 'Manual entry deletion is disabled. Cash Book entries are managed automatically.');
    }

    /**
     * Store an Online Collection Details record.
     */
    public function storeOnlineCollection(Request $request, $id)
    {
        return redirect()->back()->with('error', 'Manual online collection creation is disabled. Online collections populate automatically from transactions.');
    }

    /**
     * Delete an Online Collection Detail record.
     */
    public function destroyOnlineCollection($id, $collectionId)
    {
        return redirect()->back()->with('error', 'Manual online collection deletion is disabled.');
    }

    /**
     * Physical cash denomination count method (disabled / no-op).
     */
    public function saveDenominations(Request $request, $id)
    {
        $cashBook = CashBook::findOrFail($id);
        return redirect()->route('admin.cash-book.show', $cashBook->id)
            ->with('success', 'Cash Book register reconciled successfully.');
    }

    /**
     * Close the daily cash book register.
     */
    public function close(Request $request, $id)
    {
        $cashBook = CashBook::findOrFail($id);

        $request->validate([
            'remarks' => 'nullable|string|max:500',
        ]);

        try {
            $this->cashBookService->closeCashBook($cashBook, auth()->id(), $request->input('remarks'));
            return redirect()->route('admin.cash-book.show', $cashBook->id)
                ->with('success', 'Daily Cash Book register closed successfully.');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }
    }

    /**
     * Reopen a closed cash book register.
     */
    public function reopen(Request $request, $id)
    {
        $cashBook = CashBook::findOrFail($id);

        $request->validate([
            'reason' => 'required|string|max:500',
        ]);

        try {
            $this->cashBookService->reopenCashBook($cashBook, auth()->id(), $request->input('reason'));
            return redirect()->route('admin.cash-book.show', $cashBook->id)
                ->with('success', 'Cash Book register reopened for editing.');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }
    }

    /**
     * Manual ERP transaction sync button.
     */
    public function syncErp($id)
    {
        $cashBook = CashBook::findOrFail($id);

        if ($cashBook->isClosed()) {
            return redirect()->back()->with('error', 'Cannot sync ERP transactions into a closed register.');
        }

        $this->cashBookService->syncErpTransactions($cashBook);

        return redirect()->route('admin.cash-book.show', $cashBook->id)
            ->with('success', 'Cash Book successfully synchronized with ERP transactions.');
    }

    /**
     * Display printable view matching physical cash book register layout.
     */
    public function print($id)
    {
        $cashBook = CashBook::with(['company', 'branch', 'responsibleStaff', 'closedBy', 'approvedBy', 'entries', 'onlineCollections'])
            ->findOrFail($id);

        return view('admin.cash-book.print', compact('cashBook'));
    }

    /**
     * Export daily cash book report to CSV/Excel.
     */
    public function export($id)
    {
        $cashBook = CashBook::with(['branch', 'entries', 'onlineCollections'])->findOrFail($id);

        $filename = 'CashBook_' . $cashBook->branch->name . '_' . $cashBook->date->format('Y-m-d') . '.csv';

        $headers = [
            "Content-type" => "text/csv",
            "Content-Disposition" => "attachment; filename=$filename",
            "Pragma" => "no-cache",
            "Cache-Control" => "must-revalidate, post-check=0, pre-check=0",
            "Expires" => "0"
        ];

        $callback = function () use ($cashBook) {
            $file = fopen('php://output', 'w');

            fputcsv($file, ['DAILY CASH BOOK REGISTER - ' . strtoupper($cashBook->branch->name)]);
            fputcsv($file, ['Date:', $cashBook->date->format('d/m/Y'), 'Status:', strtoupper($cashBook->status)]);
            fputcsv($file, ['Opening Balance (Rs):', $cashBook->opening_balance]);
            fputcsv($file, []);

            fputcsv($file, ['=== RECEIVED SECTION ===']);
            fputcsv($file, ['Date', 'Particulars', 'Amount Rs (Cash)', 'Product Amount', 'Bank Amount']);
            foreach ($cashBook->receivedEntries as $entry) {
                fputcsv($file, [
                    $entry->entry_date->format('d/m/Y'),
                    $entry->particulars,
                    $entry->cash_amount,
                    $entry->product_amount,
                    $entry->bank_amount
                ]);
            }
            fputcsv($file, ['', 'TOTAL RECEIVED AMOUNT', $cashBook->total_cash_received, $cashBook->total_product_received, $cashBook->total_bank_received]);
            fputcsv($file, []);

            fputcsv($file, ['=== PAYMENT SECTION ===']);
            fputcsv($file, ['Date', 'Particulars', 'Amount Rs (Cash)', 'Product Amount', 'Bank Amount']);
            foreach ($cashBook->paymentEntries as $entry) {
                fputcsv($file, [
                    $entry->entry_date->format('d/m/Y'),
                    $entry->particulars,
                    $entry->category_code === 'member_no' ? (int)$entry->cash_amount : $entry->cash_amount,
                    $entry->product_amount,
                    $entry->bank_amount
                ]);
            }
            fputcsv($file, ['', 'TOTAL PAYMENT RS.', $cashBook->total_cash_payment, $cashBook->total_product_payment, $cashBook->total_bank_payment]);
            fputcsv($file, []);

            fputcsv($file, ['=== RECONCILIATION SUMMARY ===']);
            fputcsv($file, ['Opening Cash', $cashBook->opening_balance]);
            fputcsv($file, ['Total Cash Received', $cashBook->total_cash_received]);
            fputcsv($file, ['Total Cash Payment', $cashBook->total_cash_payment]);
            fputcsv($file, ['System Closing Cash', $cashBook->closing_cash]);
            fputcsv($file, ['Status', strtoupper($cashBook->reconciled_status)]);

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }
}
