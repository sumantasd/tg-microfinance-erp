<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Services\ReportService;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReportApiController extends Controller
{
    use ApiResponse;

    public function __construct(protected ReportService $reportService) {}

    /**
     * Resolve Company and Branch Scope for Mobile User.
     */
    protected function resolveCompanyAndBranch(Request $request): array
    {
        $user = $request->user();
        $companyId = $user ? $user->resolveScopedCompanyId($request->filled('company_id') ? (int) $request->input('company_id') : null) : 1;
        $branchId = $user ? $user->resolveScopedBranchId($request->filled('branch_id') ? (int) $request->input('branch_id') : null) : null;

        return [$companyId, $branchId];
    }

    /**
     * Get list of permitted report categories & types.
     */
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        $allCategories = $this->reportService->getAvailableCategories();
        $accessibleCategories = [];

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

        return $this->successResponse($accessibleCategories, 'Available report categories retrieved successfully');
    }

    /**
     * Generate mobile report data with KPIs, filters, and paginated rows.
     */
    public function show(Request $request, string $category, string $type): JsonResponse
    {
        [$companyId, $branchId] = $this->resolveCompanyAndBranch($request);
        $categories = $this->reportService->getAvailableCategories();

        if (!isset($categories[$category]) || !isset($categories[$category]['reports'][$type])) {
            return $this->errorResponse('The requested report type was not found.', 404);
        }

        $user = $request->user();
        $hasPermission = match ($category) {
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

        if (!$hasPermission) {
            return $this->errorResponse('Unauthorized to view this report category.', 403);
        }

        $filters = $request->all();
        $reportData = $this->reportService->generateReport(
            $category,
            $type,
            $companyId,
            $branchId,
            $filters,
            paginate: true,
            perPage: $request->input('per_page', 25)
        );

        return $this->successResponse([
            'category' => $category,
            'type' => $type,
            'report_meta' => $categories[$category]['reports'][$type],
            'company_id' => $companyId,
            'branch_id' => $branchId,
            'kpis' => $reportData['kpis'] ?? [],
            'columns' => $reportData['columns'] ?? [],
            'rows' => $reportData['rows'] ?? [],
        ], 'Report data retrieved successfully');
    }

    /**
     * CSV Export for authorized mobile users.
     */
    public function export(Request $request, string $category, string $type): StreamedResponse|JsonResponse
    {
        $user = $request->user();
        if (!$user->can('reports.export') && !$user->can('reports.view') && !$user->isSuperAdmin()) {
            return $this->errorResponse('You do not have permission to export reports.', 403);
        }

        [$companyId, $branchId] = $this->resolveCompanyAndBranch($request);
        $categories = $this->reportService->getAvailableCategories();

        if (!isset($categories[$category]) || !isset($categories[$category]['reports'][$type])) {
            return $this->errorResponse('The requested report type was not found.', 404);
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
            fputs($handle, chr(0xEF) . chr(0xBB) . chr(0xBF)); // UTF-8 BOM

            $headers = array_values($reportData['columns']);
            fputcsv($handle, $headers);

            $columnKeys = array_keys($reportData['columns']);
            foreach ($reportData['rows'] as $row) {
                $line = [];
                foreach ($columnKeys as $key) {
                    $val = $row[$key] ?? '';
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
