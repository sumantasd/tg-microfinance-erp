<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\Expense;
use App\Models\ExpenseCategory;
use App\Models\Supplier;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class ExpenseReportController extends Controller
{
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

    public function index(Request $request): View
    {
        $user = Auth::user();
        $branches = $this->getAccessibleBranches();
        $branchIds = $branches->pluck('id');

        $reportType = $request->get('report_type', 'summary');

        $query = Expense::whereIn('branch_id', $branchIds)
            ->with(['branch', 'category', 'supplier', 'requestedBy', 'approvedBy']);

        // Filters
        if ($request->filled('date_from')) {
            $query->whereDate('expense_date', '>=', $request->get('date_from'));
        }
        if ($request->filled('date_to')) {
            $query->whereDate('expense_date', '<=', $request->get('date_to'));
        }
        if ($request->filled('branch_id')) {
            $query->where('branch_id', $request->get('branch_id'));
        }
        if ($request->filled('category_id')) {
            $query->where('expense_category_id', $request->get('category_id'));
        }
        if ($request->filled('supplier_id')) {
            $query->where('supplier_id', $request->get('supplier_id'));
        }
        if ($request->filled('status')) {
            $query->where('status', $request->get('status'));
        }
        if ($request->filled('payment_status')) {
            $query->where('payment_status', $request->get('payment_status'));
        }

        $expenses = $query->orderByDesc('expense_date')->get();

        // Calculations for summary metrics
        $totalExpense = $expenses->whereNotIn('status', ['CANCELLED', 'REJECTED'])->sum('total_amount');
        $totalPaid = $expenses->whereNotIn('status', ['CANCELLED', 'REJECTED'])->sum('paid_amount');
        $totalOutstanding = $expenses->whereNotIn('status', ['CANCELLED', 'REJECTED'])->sum('outstanding_amount');
        $totalCount = $expenses->count();

        // Grouping logic according to report_type
        $groupedData = collect();
        if ($reportType === 'by_category') {
            $groupedData = $expenses->groupBy('expense_category_id')->map(function ($items) {
                return [
                    'label' => $items->first()->category->category_name ?? 'Uncategorized',
                    'count' => $items->count(),
                    'total' => $items->whereNotIn('status', ['CANCELLED', 'REJECTED'])->sum('total_amount'),
                    'paid' => $items->whereNotIn('status', ['CANCELLED', 'REJECTED'])->sum('paid_amount'),
                    'outstanding' => $items->whereNotIn('status', ['CANCELLED', 'REJECTED'])->sum('outstanding_amount'),
                ];
            });
        } elseif ($reportType === 'by_branch') {
            $groupedData = $expenses->groupBy('branch_id')->map(function ($items) {
                return [
                    'label' => $items->first()->branch->name ?? 'N/A',
                    'count' => $items->count(),
                    'total' => $items->whereNotIn('status', ['CANCELLED', 'REJECTED'])->sum('total_amount'),
                    'paid' => $items->whereNotIn('status', ['CANCELLED', 'REJECTED'])->sum('paid_amount'),
                    'outstanding' => $items->whereNotIn('status', ['CANCELLED', 'REJECTED'])->sum('outstanding_amount'),
                ];
            });
        } elseif ($reportType === 'by_payee') {
            $groupedData = $expenses->groupBy(function ($item) {
                return $item->supplier_id ? ('sup_' . $item->supplier_id) : ('payee_' . ($item->payee_name ?: 'Other'));
            })->map(function ($items) {
                $first = $items->first();
                return [
                    'label' => $first->payee_display_name,
                    'count' => $items->count(),
                    'total' => $items->whereNotIn('status', ['CANCELLED', 'REJECTED'])->sum('total_amount'),
                    'paid' => $items->whereNotIn('status', ['CANCELLED', 'REJECTED'])->sum('paid_amount'),
                    'outstanding' => $items->whereNotIn('status', ['CANCELLED', 'REJECTED'])->sum('outstanding_amount'),
                ];
            });
        }

        $categories = ExpenseCategory::where('is_active', true)->orderBy('category_name')->get();
        $suppliers = Supplier::where('status', 'active')->orderBy('supplier_name')->get();

        return view('admin.expenses.reports.index', compact(
            'expenses',
            'branches',
            'categories',
            'suppliers',
            'reportType',
            'groupedData',
            'totalExpense',
            'totalPaid',
            'totalOutstanding',
            'totalCount'
        ));
    }

    public function export(Request $request)
    {
        $user = Auth::user();
        if (!$user->can('expense.report.export')) {
            abort(403, 'Unauthorized to export expense reports.');
        }

        $branches = $this->getAccessibleBranches();
        $branchIds = $branches->pluck('id');

        $query = Expense::whereIn('branch_id', $branchIds)
            ->with(['branch', 'category', 'supplier', 'requestedBy', 'approvedBy']);

        if ($request->filled('date_from')) {
            $query->whereDate('expense_date', '>=', $request->get('date_from'));
        }
        if ($request->filled('date_to')) {
            $query->whereDate('expense_date', '<=', $request->get('date_to'));
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

        $expenses = $query->orderByDesc('expense_date')->get();

        $fileName = 'expense_report_' . date('Ymd_His') . '.csv';

        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"$fileName\"",
            'Pragma' => 'no-cache',
            'Cache-Control' => 'must-revalidate, post-check=0, pre-check=0',
            'Expires' => '0',
        ];

        $columns = ['Expense Number', 'Date', 'Branch', 'Category', 'Payee', 'Description', 'Amount', 'Tax', 'Total Amount', 'Paid Amount', 'Outstanding', 'Status', 'Requested By'];

        $callback = function () use ($expenses, $columns) {
            $file = fopen('php://output', 'w');
            fputcsv($file, $columns);

            foreach ($expenses as $row) {
                fputcsv($file, [
                    $row->expense_number,
                    $row->expense_date->format('Y-m-d'),
                    $row->branch?->name,
                    $row->category?->category_name,
                    $row->payee_display_name,
                    $row->description,
                    $row->amount,
                    $row->tax_amount,
                    $row->total_amount,
                    $row->paid_amount,
                    $row->outstanding_amount,
                    $row->status,
                    $row->requestedBy?->name,
                ]);
            }
            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }
}
