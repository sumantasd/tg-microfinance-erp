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
        $selectedBranchId = $request->input('branch_id', $user->branch_id ?? Branch::first()?->id);
        $selectedDate = $request->input('date', today()->format('Y-m-d'));

        $branches = Branch::where('is_active', true)->get();

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

        return view('admin.cash-book.index', compact('cashBooks', 'branches', 'selectedBranchId', 'selectedDate', 'currentCashBook'));
    }

    /**
     * Display the Daily Cash Book Register screen (2-column layout matching physical register).
     */
    public function show(Request $request, $id)
    {
        $cashBook = CashBook::with(['branch', 'responsibleStaff', 'closedBy', 'approvedBy', 'entries', 'onlineCollections', 'denominations', 'audits.user'])
            ->findOrFail($id);

        // Trigger ERP sync if open
        if ($cashBook->isOpen()) {
            $this->cashBookService->syncErpTransactions($cashBook);
            $cashBook->refresh();
        }

        $customers = Customer::where('branch_id', $cashBook->branch_id)->orderBy('first_name')->get();
        $customerGroups = CustomerGroup::where('branch_id', $cashBook->branch_id)->orderBy('name')->get();

        return view('admin.cash-book.show', compact('cashBook', 'customers', 'customerGroups'));
    }

    /**
     * Store or update a Cash Book Particular entry item.
     */
    public function storeEntry(Request $request, $id)
    {
        $cashBook = CashBook::findOrFail($id);

        $validated = $request->validate([
            'id' => 'nullable|exists:cash_book_entries,id',
            'entry_type' => 'required|in:received,payment',
            'category_code' => 'nullable|string|max:50',
            'particulars' => 'required|string|max:255',
            'cash_amount' => 'required|numeric|min:0',
            'product_amount' => 'nullable|numeric|min:0',
            'bank_amount' => 'nullable|numeric|min:0',
            'remarks' => 'nullable|string|max:500',
        ]);

        try {
            $this->cashBookService->addOrUpdateEntry($cashBook, $validated, auth()->id());
            return redirect()->route('admin.cash-book.show', $cashBook->id)
                ->with('success', 'Particular item saved successfully.');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }
    }

    /**
     * Remove a custom entry item from the Cash Book.
     */
    public function destroyEntry($id, $entryId)
    {
        $cashBook = CashBook::findOrFail($id);

        if ($cashBook->isClosed()) {
            return redirect()->back()->with('error', 'Cannot modify a closed Cash Book register.');
        }

        $entry = CashBookEntry::where('cash_book_id', $cashBook->id)->where('id', $entryId)->firstOrFail();
        $entry->delete();

        $this->cashBookService->recalculateTotals($cashBook);

        return redirect()->route('admin.cash-book.show', $cashBook->id)
            ->with('success', 'Particular item deleted successfully.');
    }

    /**
     * Store an Online Collection Details record.
     */
    public function storeOnlineCollection(Request $request, $id)
    {
        $cashBook = CashBook::findOrFail($id);

        $validated = $request->validate([
            'collection_date' => 'required|date',
            'customer_id' => 'nullable|exists:customers,id',
            'customer_name' => 'required|string|max:255',
            'amount' => 'required|numeric|min:0.01',
            'group_id' => 'nullable|exists:customer_groups,id',
            'group_name' => 'nullable|string|max:255',
            'mobile_no' => 'nullable|string|max:30',
            'payment_method' => 'nullable|string|max:30',
            'transaction_reference' => 'nullable|string|max:100',
        ]);

        try {
            $this->cashBookService->addOnlineCollection($cashBook, $validated, auth()->id());
            return redirect()->route('admin.cash-book.show', $cashBook->id)
                ->with('success', 'Online collection detail added successfully.');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }
    }

    /**
     * Delete an Online Collection Detail record.
     */
    public function destroyOnlineCollection($id, $collectionId)
    {
        $cashBook = CashBook::findOrFail($id);

        if ($cashBook->isClosed()) {
            return redirect()->back()->with('error', 'Cannot modify a closed Cash Book register.');
        }

        $collection = CashBookOnlineCollection::where('cash_book_id', $cashBook->id)->where('id', $collectionId)->firstOrFail();
        $collection->delete();

        return redirect()->route('admin.cash-book.show', $cashBook->id)
            ->with('success', 'Online collection detail removed.');
    }

    /**
     * Update physical cash denomination counts and recalculate reconciliation.
     */
    public function saveDenominations(Request $request, $id)
    {
        $cashBook = CashBook::findOrFail($id);

        $validated = $request->validate([
            'denominations' => 'required|array',
            'denominations.*' => 'nullable|integer|min:0',
        ]);

        try {
            $this->cashBookService->updateDenominations($cashBook, $validated['denominations'], auth()->id());
            return redirect()->route('admin.cash-book.show', $cashBook->id)
                ->with('success', 'Physical cash denomination reconciled successfully.');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }
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
        $cashBook = CashBook::with(['company', 'branch', 'responsibleStaff', 'closedBy', 'approvedBy', 'entries', 'onlineCollections', 'denominations'])
            ->findOrFail($id);

        return view('admin.cash-book.print', compact('cashBook'));
    }

    /**
     * Export daily cash book report to CSV/Excel.
     */
    public function export($id)
    {
        $cashBook = CashBook::with(['branch', 'entries', 'onlineCollections', 'denominations'])->findOrFail($id);

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
                    $entry->cash_amount,
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
            fputcsv($file, ['Physical Cash Total', $cashBook->physical_cash]);
            fputcsv($file, ['Cash Difference', $cashBook->cash_difference]);
            fputcsv($file, ['Status', strtoupper($cashBook->reconciled_status)]);

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }
}
