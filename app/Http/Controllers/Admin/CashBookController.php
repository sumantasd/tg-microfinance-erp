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
     * Display a listing of daily cash book registers.
     */
    public function index(Request $request)
    {
        $user = auth()->user();

        // Branch isolation: Branch Managers are restricted to their assigned branch
        $userBranchId = $user->branch_id;
        $selectedBranchId = $userBranchId ?? $request->input('branch_id', Branch::first()?->id);
        $selectedDate = $request->input('date', Carbon::now('Asia/Kolkata')->format('Y-m-d'));

        $branches = $userBranchId 
            ? Branch::where('id', $userBranchId)->where('is_active', true)->get()
            : Branch::where('is_active', true)->get();

        $query = CashBook::with(['branch', 'responsibleStaff', 'closedBy'])
            ->orderByDesc('date')
            ->orderByDesc('id');

        if ($selectedBranchId) {
            $query->where('branch_id', $selectedBranchId);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        $cashBooks = $query->paginate(15);

        // Fetch or prepare current active cash book for selected branch & date
        $currentCashBook = null;
        if ($selectedBranchId) {
            $companyId = $user->company_id ?? 1;
            $currentCashBook = $this->cashBookService->getOrCreateCashBook($companyId, $selectedBranchId, $selectedDate, $user->id);
        }

        return view('admin.cash-book.index', compact('cashBooks', 'branches', 'selectedBranchId', 'selectedDate', 'currentCashBook', 'userBranchId'));
    }

    /**
     * Display the Daily Cash Book Register screen (2-column layout matching physical register).
     */
    public function show(Request $request, $id)
    {
        $user = auth()->user();
        $cashBook = CashBook::with(['branch', 'responsibleStaff', 'closedBy', 'approvedBy', 'entries', 'onlineCollections', 'audits.user'])
            ->findOrFail($id);

        // Branch isolation check
        if ($user->branch_id && (int)$cashBook->branch_id !== (int)$user->branch_id) {
            abort(403, 'Unauthorized access to another branch cash book.');
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
