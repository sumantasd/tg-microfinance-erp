<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\CustomerGroup;
use App\Models\Employee;
use App\Models\LoanAccount;
use App\Models\LoanApplication;
use App\Models\LoanScheme;
use App\Models\Product;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class GlobalSearchController extends Controller
{
    /**
     * Handle Global Unified Admin Search across 7 ERP Entities.
     */
    public function search(Request $request): View|JsonResponse
    {
        $user = Auth::user();
        $query = trim((string) $request->input('q', ''));
        $format = $request->input('format');

        $companyId = $user ? $user->resolveScopedCompanyId() : 1;
        $branchId = $user ? $user->resolveScopedBranchId() : null;

        if (strlen($query) === 0) {
            if ($request->expectsJson() || $format === 'json') {
                return response()->json([
                    'success' => true,
                    'query' => '',
                    'total_results' => 0,
                    'categories' => [],
                ]);
            }

            return view('admin.search.results', [
                'query' => '',
                'totalCount' => 0,
                'categories' => [],
            ]);
        }

        $categories = [];
        $totalCount = 0;
        $entityLimit = 10;
        $maxTotalLimit = 50;

        // 1. Customers Search (requires customer.view)
        if ($user && $user->can('customer.view')) {
            $customerQuery = Customer::where('company_id', $companyId)
                ->when($branchId, fn($q) => $q->where('branch_id', $branchId))
                ->where(function ($q) use ($query) {
                    $q->where('first_name', 'like', "%{$query}%")
                      ->orWhere('last_name', 'like', "%{$query}%")
                      ->orWhere('customer_code', 'like', "%{$query}%")
                      ->orWhere('mobile_number', 'like', "%{$query}%")
                      ->orWhere('member_number', 'like', "%{$query}%");
                })
                ->limit($entityLimit)
                ->get();

            if ($customerQuery->isNotEmpty()) {
                $categories['Customers'] = $customerQuery->map(fn($c) => [
                    'id' => $c->id,
                    'title' => $c->full_name ?: ($c->first_name . ' ' . $c->last_name),
                    'subtitle' => "ID: {$c->customer_code} | Mobile: {$c->mobile_number}",
                    'url' => route('admin.customer.show', $c->id),
                    'badge' => ucfirst($c->status ?? 'active'),
                    'badge_class' => ($c->status === 'active') ? 'bg-success' : 'bg-secondary',
                    'icon' => 'bi-person-badge',
                    'type' => 'Customer',
                ])->toArray();
                $totalCount += count($categories['Customers']);
            }
        }

        // 2. Customer Groups Search (requires group.view)
        if ($user && $user->can('group.view') && $totalCount < $maxTotalLimit) {
            $groupQuery = CustomerGroup::where('company_id', $companyId)
                ->when($branchId, fn($q) => $q->where('branch_id', $branchId))
                ->where(function ($q) use ($query) {
                    $q->where('name', 'like', "%{$query}%")
                      ->orWhere('group_code', 'like', "%{$query}%");
                })
                ->limit($entityLimit)
                ->get();

            if ($groupQuery->isNotEmpty()) {
                $categories['Customer Groups'] = $groupQuery->map(fn($g) => [
                    'id' => $g->id,
                    'title' => $g->name,
                    'subtitle' => "Group Code: {$g->group_code} | Status: " . ucfirst($g->status ?? 'active'),
                    'url' => route('admin.customer-group.show', $g->id),
                    'badge' => ucfirst($g->status ?? 'active'),
                    'badge_class' => 'bg-info text-white',
                    'icon' => 'bi-people-fill',
                    'type' => 'Customer Group',
                ])->toArray();
                $totalCount += count($categories['Customer Groups']);
            }
        }

        // 3. Loan Accounts Search (requires loan.view)
        if ($user && $user->can('loan.view') && $totalCount < $maxTotalLimit) {
            $loanQuery = LoanAccount::where('loan_accounts.company_id', $companyId)
                ->when($branchId, fn($q) => $q->where('loan_accounts.branch_id', $branchId))
                ->with(['customer', 'loanScheme'])
                ->where(function ($q) use ($query) {
                    $q->where('loan_accounts.loan_number', 'like', "%{$query}%")
                      ->orWhereHas('customer', function ($cq) use ($query) {
                          $cq->where('first_name', 'like', "%{$query}%")
                             ->orWhere('last_name', 'like', "%{$query}%")
                             ->orWhere('customer_code', 'like', "%{$query}%")
                             ->orWhere('mobile_number', 'like', "%{$query}%");
                      });
                })
                ->limit($entityLimit)
                ->get();

            if ($loanQuery->isNotEmpty()) {
                $categories['Loan Accounts'] = $loanQuery->map(fn($l) => [
                    'id' => $l->id,
                    'title' => $l->loan_number,
                    'subtitle' => "Borrower: " . ($l->customer?->full_name ?? 'N/A') . " | Outstanding: ₹" . number_format($l->total_outstanding, 2),
                    'url' => route('admin.loan-account.show', $l->id),
                    'badge' => ucfirst($l->status),
                    'badge_class' => match($l->status) {
                        'active' => 'bg-success',
                        'closed' => 'bg-secondary',
                        'defaulted' => 'bg-danger',
                        default => 'bg-primary'
                    },
                    'icon' => 'bi-wallet2',
                    'type' => 'Loan Account',
                ])->toArray();
                $totalCount += count($categories['Loan Accounts']);
            }
        }

        // 4. Loan Applications Search (requires loan_application.view)
        if ($user && $user->can('loan_application.view') && $totalCount < $maxTotalLimit) {
            $appQuery = LoanApplication::where('loan_applications.company_id', $companyId)
                ->when($branchId, fn($q) => $q->where('loan_applications.branch_id', $branchId))
                ->with(['customer', 'loanScheme'])
                ->where(function ($q) use ($query) {
                    $q->where('loan_applications.application_number', 'like', "%{$query}%")
                      ->orWhereHas('customer', function ($cq) use ($query) {
                          $cq->where('first_name', 'like', "%{$query}%")
                             ->orWhere('last_name', 'like', "%{$query}%");
                      });
                })
                ->limit($entityLimit)
                ->get();

            if ($appQuery->isNotEmpty()) {
                $categories['Loan Applications'] = $appQuery->map(fn($a) => [
                    'id' => $a->id,
                    'title' => $a->application_number,
                    'subtitle' => "Applicant: " . ($a->customer?->full_name ?? 'N/A') . " | Requested: ₹" . number_format($a->requested_amount, 2),
                    'url' => route('admin.loan-application.show', $a->id),
                    'badge' => ucfirst(str_replace('_', ' ', $a->status)),
                    'badge_class' => match($a->status) {
                        'approved' => 'bg-success',
                        'rejected' => 'bg-danger',
                        'under_review', 'submitted' => 'bg-warning text-dark',
                        default => 'bg-secondary'
                    },
                    'icon' => 'bi-file-earmark-spreadsheet',
                    'type' => 'Loan Application',
                ])->toArray();
                $totalCount += count($categories['Loan Applications']);
            }
        }

        // 5. Loan Schemes Search (requires loan_scheme.view)
        if ($user && $user->can('loan_scheme.view') && $totalCount < $maxTotalLimit) {
            $schemeQuery = LoanScheme::where('company_id', $companyId)
                ->where(function ($q) use ($query) {
                    $q->where('name', 'like', "%{$query}%")
                      ->orWhere('code', 'like', "%{$query}%");
                })
                ->limit($entityLimit)
                ->get();

            if ($schemeQuery->isNotEmpty()) {
                $categories['Loan Schemes'] = $schemeQuery->map(fn($s) => [
                    'id' => $s->id,
                    'title' => $s->name,
                    'subtitle' => "Code: {$s->code} | Type: " . ucfirst($s->loan_type) . " | Rate: {$s->interest_rate_per_annum}% p.a.",
                    'url' => route('admin.loan-scheme.show', $s->id),
                    'badge' => ucfirst($s->loan_type),
                    'badge_class' => 'bg-dark',
                    'icon' => 'bi-journal-bookmark',
                    'type' => 'Loan Scheme',
                ])->toArray();
                $totalCount += count($categories['Loan Schemes']);
            }
        }

        // 6. Products Search (requires product.view)
        if ($user && $user->can('product.view') && $totalCount < $maxTotalLimit) {
            $productQuery = Product::where('company_id', $companyId)
                ->where(function ($q) use ($query) {
                    $q->where('name', 'like', "%{$query}%")
                      ->orWhere('sku', 'like', "%{$query}%")
                      ->orWhere('model_number', 'like', "%{$query}%")
                      ->orWhere('barcode', 'like', "%{$query}%");
                })
                ->limit($entityLimit)
                ->get();

            if ($productQuery->isNotEmpty()) {
                $categories['Products'] = $productQuery->map(fn($p) => [
                    'id' => $p->id,
                    'title' => $p->name,
                    'subtitle' => "SKU: {$p->sku} | Price: ₹" . number_format($p->unit_price, 2),
                    'url' => route('admin.product.show', $p->id),
                    'badge' => '₹' . number_format($p->unit_price, 2),
                    'badge_class' => 'bg-success',
                    'icon' => 'bi-box-seam',
                    'type' => 'Product',
                ])->toArray();
                $totalCount += count($categories['Products']);
            }
        }

        // 7. Employees Search (requires employee.view)
        if ($user && $user->can('employee.view') && $totalCount < $maxTotalLimit) {
            $employeeQuery = Employee::where('company_id', $companyId)
                ->when($branchId, fn($q) => $q->where('branch_id', $branchId))
                ->with(['designation', 'department'])
                ->where(function ($q) use ($query) {
                    $q->where('first_name', 'like', "%{$query}%")
                      ->orWhere('last_name', 'like', "%{$query}%")
                      ->orWhere('employee_code', 'like', "%{$query}%")
                      ->orWhere('phone', 'like', "%{$query}%")
                      ->orWhere('email', 'like', "%{$query}%");
                })
                ->limit($entityLimit)
                ->get();

            if ($employeeQuery->isNotEmpty()) {
                $categories['Employees'] = $employeeQuery->map(fn($e) => [
                    'id' => $e->id,
                    'title' => "{$e->first_name} {$e->last_name}",
                    'subtitle' => "Code: {$e->employee_code} | " . ($e->designation?->name ?? 'Staff') . " | Phone: {$e->phone}",
                    'url' => route('admin.employee.show', $e->id),
                    'badge' => ucfirst($e->status ?? 'active'),
                    'badge_class' => 'bg-primary',
                    'icon' => 'bi-person-vcard',
                    'type' => 'Employee',
                ])->toArray();
                $totalCount += count($categories['Employees']);
            }
        }

        if ($request->expectsJson() || $format === 'json') {
            return response()->json([
                'success' => true,
                'query' => $query,
                'total_results' => $totalCount,
                'categories' => $categories,
            ]);
        }

        return view('admin.search.results', compact('query', 'totalCount', 'categories'));
    }
}
