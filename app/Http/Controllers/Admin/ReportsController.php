<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\Company;
use App\Models\LoanScheme;
use App\Models\User;
use App\Services\ReportService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReportsController extends Controller
{
    public function __construct(protected ReportService $reportService) {}

    /**
     * Resolve Company and Branch Scope Authoritatively.
     */
    protected function resolveCompanyAndBranch(Request $request): array
    {
        $user = Auth::user();
        $companyId = $user ? $user->resolveScopedCompanyId($request->filled('company_id') ? (int) $request->input('company_id') : null) : 1;
        $branchId = $user ? $user->resolveScopedBranchId($request->filled('branch_id') ? (int) $request->input('branch_id') : null) : null;

        return [$companyId, $branchId];
    }

    /**
     * Central Report Center Dashboard.
     */
    public function index(Request $request): View
    {
        [$companyId, $branchId] = $this->resolveCompanyAndBranch($request);
        $user = Auth::user();

        $allCategories = $this->reportService->getAvailableCategories();
        $accessibleCategories = [];

        // Filter categories according to user permissions
        foreach ($allCategories as $catKey => $catData) {
            $hasPermission = match ($catKey) {
                'loan' => $user->can('loan.view') || $user->can('reports.view'),
                'collection' => $user->can('collection.view') || $user->can('reports.view'),
                'customer' => $user->can('customer.view') || $user->can('reports.view'),
                'overdue' => $user->can('overdue.view') || $user->can('reports.view'),
                'penalty' => $user->can('penalty.view') || $user->can('reports.view'),
                'inventory' => $user->can('inventory.view') || $user->can('reports.view'),
                'accounting' => $user->can('accounting.view') || $user->can('reports.view'),
                'hr' => $user->can('employee.view') || $user->can('hr_reports.view') || $user->can('reports.view'),
                'management' => $user->can('reports.view') || $user->isSuperAdmin() || $user->isCompanyAdmin(),
                default => $user->can('reports.view'),
            };

            if ($hasPermission) {
                $accessibleCategories[$catKey] = $catData;
            }
        }

        return view('admin.reports.index', [
            'categories' => $accessibleCategories,
            'companyId' => $companyId,
            'branchId' => $branchId,
        ]);
    }

    /**
     * Display a Specific Report with Dynamic Filters & KPIs.
     */
    public function show(Request $request, string $category, string $type): View
    {
        [$companyId, $branchId] = $this->resolveCompanyAndBranch($request);

        $categories = $this->reportService->getAvailableCategories();
        if (!isset($categories[$category]) || !isset($categories[$category]['reports'][$type])) {
            abort(404, 'The requested report was not found.');
        }

        $reportMeta = $categories[$category]['reports'][$type];
        $filters = $request->all();

        $reportData = $this->reportService->generateReport(
            $category,
            $type,
            $companyId,
            $branchId,
            $filters,
            paginate: true,
            perPage: 25
        );

        $user = Auth::user();
        $companies = $user->isSuperAdmin() ? Company::where('is_active', true)->get() : Company::where('id', $companyId)->get();
        $branches = $user->canAccessCompany($companyId)
            ? ($user->branch_id && !$user->isCompanyAdmin() ? Branch::where('id', $user->branch_id)->get() : Branch::where('company_id', $companyId)->where('is_active', true)->get())
            : Branch::where('id', $branchId)->get();

        $loanSchemes = LoanScheme::where('company_id', $companyId)->where('is_active', true)->get();

        return view('admin.reports.show', [
            'category' => $category,
            'type' => $type,
            'categoryMeta' => $categories[$category],
            'reportMeta' => $reportMeta,
            'reportData' => $reportData,
            'companies' => $companies,
            'branches' => $branches,
            'loanSchemes' => $loanSchemes,
            'companyId' => $companyId,
            'branchId' => $branchId,
            'filters' => $filters,
        ]);
    }

    /**
     * Print View for a Report.
     */
    public function print(Request $request, string $category, string $type): View
    {
        [$companyId, $branchId] = $this->resolveCompanyAndBranch($request);

        $categories = $this->reportService->getAvailableCategories();
        if (!isset($categories[$category]) || !isset($categories[$category]['reports'][$type])) {
            abort(404, 'The requested report was not found.');
        }

        $reportMeta = $categories[$category]['reports'][$type];
        $filters = $request->all();

        $reportData = $this->reportService->generateReport(
            $category,
            $type,
            $companyId,
            $branchId,
            $filters,
            paginate: false
        );

        $company = Company::find($companyId);
        $branch = $branchId ? Branch::find($branchId) : null;

        return view('admin.reports.print', [
            'category' => $category,
            'type' => $type,
            'reportMeta' => $reportMeta,
            'reportData' => $reportData,
            'company' => $company,
            'branch' => $branch,
            'filters' => $filters,
        ]);
    }

    /**
     * Export Report Data as CSV Stream.
     */
    public function export(Request $request, string $category, string $type): StreamedResponse
    {
        // Enforce reports.export permission check
        if (!Auth::user()->can('reports.export')) {
            abort(403, 'You do not have permission to export reports.');
        }

        [$companyId, $branchId] = $this->resolveCompanyAndBranch($request);

        $categories = $this->reportService->getAvailableCategories();
        if (!isset($categories[$category]) || !isset($categories[$category]['reports'][$type])) {
            abort(404, 'The requested report was not found.');
        }

        $reportMeta = $categories[$category]['reports'][$type];
        $filters = $request->all();

        $reportData = $this->reportService->generateReport(
            $category,
            $type,
            $companyId,
            $branchId,
            $filters,
            paginate: false
        );

        $cleanTitle = preg_replace('/[^A-Za-z0-9_\-]/', '_', strtolower($reportMeta['title']));
        $fileName = trim(preg_replace('/_+/', '_', $cleanTitle), '_') . '_' . date('Y_m_d_His') . '.csv';

        return response()->streamDownload(function () use ($reportData) {
            $handle = fopen('php://output', 'w');

            // Add UTF-8 BOM for Microsoft Excel compatibility
            fputs($handle, chr(0xEF) . chr(0xBB) . chr(0xBF));

            // Write Header
            $headers = array_values($reportData['columns']);
            fputcsv($handle, $headers);

            // Write Data Rows
            $columnKeys = array_keys($reportData['columns']);
            foreach ($reportData['rows'] as $row) {
                $line = [];
                foreach ($columnKeys as $key) {
                    $val = $row[$key] ?? '';
                    // Clean currency symbols for numeric columns
                    $line[] = is_string($val) ? trim(str_replace('₹', '', $val)) : $val;
                }
                fputcsv($handle, $line);
            }

            fclose($handle);
        }, $fileName, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$fileName}\"",
        ]);
    }
}
