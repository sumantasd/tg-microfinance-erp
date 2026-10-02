<?php

use App\Http\Controllers\Api\V1\AttendanceApiController;
use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\BankDepositApiController;
use App\Http\Controllers\Api\V1\CashBookApiController;
use App\Http\Controllers\Api\V1\CollectionApiController;
use App\Http\Controllers\Api\V1\CustomerApiController;
use App\Http\Controllers\Api\V1\DashboardApiController;
use App\Http\Controllers\Api\V1\ExpenseApiController;
use App\Http\Controllers\Api\V1\GroupApiController;
use App\Http\Controllers\Api\V1\InventoryApiController;
use App\Http\Controllers\Api\V1\KycApiController;
use App\Http\Controllers\Api\V1\LoanApiController;
use App\Http\Controllers\Api\V1\HrmApiController;
use App\Http\Controllers\Api\V1\InventoryTransferApiController;
use App\Http\Controllers\Api\V1\NotificationApiController;
use App\Http\Controllers\Api\V1\ProfileApiController;
use App\Http\Controllers\Api\V1\ReportApiController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Mobile API V1 Routes
|--------------------------------------------------------------------------
*/

Route::prefix('v1')->group(function () {

    // Public Auth & App Config Endpoints
    Route::post('/auth/login', [AuthController::class, 'login'])->middleware('throttle:10,1');
    Route::get('/app-config', [ProfileApiController::class, 'appConfig']);

    // Protected API Endpoints (Sanctum Auth)
    Route::middleware(['auth:sanctum'])->group(function () {

        // Profile & Account Settings
        Route::get('/profile', [ProfileApiController::class, 'profile']);
        Route::put('/profile', [ProfileApiController::class, 'updateProfile']);
        Route::put('/profile/password', [ProfileApiController::class, 'changePassword']);
        Route::get('/audit-logs', [ProfileApiController::class, 'auditLogs']);
        Route::get('/media', [ProfileApiController::class, 'media']);

        // Auth
        Route::post('/auth/logout', [AuthController::class, 'logout']);
        Route::get('/auth/me', [AuthController::class, 'me']);

        // Dashboard Stats
        Route::get('/dashboard/stats', [DashboardApiController::class, 'stats']);

        // Customers
        Route::middleware(['can:customer.view'])->group(function () {
            Route::get('/customers', [CustomerApiController::class, 'index']);
            Route::get('/customers/{id}', [CustomerApiController::class, 'show']);
            Route::get('/customers/{id}/guarantors', [CustomerApiController::class, 'guarantors']);
            Route::get('/customers/{id}/nominees', [CustomerApiController::class, 'nominees']);
        });
        Route::middleware(['can:customer.create'])->group(function () {
            Route::post('/customers', [CustomerApiController::class, 'store']);
        });
        Route::middleware(['can:customer.edit'])->group(function () {
            Route::put('/customers/{id}', [CustomerApiController::class, 'update']);
        });
        Route::middleware(['can:customer.change_status'])->group(function () {
            Route::patch('/customers/{id}/toggle-status', [CustomerApiController::class, 'toggleStatus']);
        });
        Route::middleware(['can:customer.delete'])->group(function () {
            Route::delete('/customers/{id}', [CustomerApiController::class, 'destroy']);
        });
        Route::middleware(['can:customer.restore'])->group(function () {
            Route::post('/customers/{id}/restore', [CustomerApiController::class, 'restore']);
        });
        Route::middleware(['can:customer.manage_guarantor'])->group(function () {
            Route::post('/customers/{id}/guarantors', [CustomerApiController::class, 'storeGuarantor']);
            Route::delete('/customers/{id}/guarantors/{guarantorId}', [CustomerApiController::class, 'destroyGuarantor']);
        });
        Route::middleware(['can:customer.manage_nominee'])->group(function () {
            Route::post('/customers/{id}/nominees', [CustomerApiController::class, 'storeNominee']);
            Route::delete('/customers/{id}/nominees/{nomineeId}', [CustomerApiController::class, 'destroyNominee']);
        });

        // Customer Groups
        Route::middleware(['can:group.view'])->group(function () {
            Route::get('/groups', [GroupApiController::class, 'index']);
            Route::get('/groups/{id}', [GroupApiController::class, 'show']);
        });
        Route::middleware(['can:group.create'])->group(function () {
            Route::post('/groups', [GroupApiController::class, 'store']);
        });
        Route::middleware(['can:group.manage_members'])->group(function () {
            Route::post('/groups/{id}/members', [GroupApiController::class, 'addMember']);
            Route::delete('/groups/{id}/members/{customerId}', [GroupApiController::class, 'removeMember']);
        });

        // Loans
        Route::middleware(['can:loan.view'])->group(function () {
            Route::get('/loans/schemes', [LoanApiController::class, 'schemes']);
            Route::get('/loans/applications', [LoanApiController::class, 'applications']);
            Route::get('/loans/accounts', [LoanApiController::class, 'accounts']);
            Route::get('/loans/accounts/{id}', [LoanApiController::class, 'showAccount']);
            Route::get('/loans/accounts/{id}/schedule', [LoanApiController::class, 'schedule']);
            Route::get('/overdue', [LoanApiController::class, 'overdueList']);
            Route::get('/loans/accounts/{id}/settlement-quote', [LoanApiController::class, 'settlementQuote']);
        });
        Route::middleware(['can:loan_application.create', 'idempotent'])->group(function () {
            Route::post('/loans/applications', [LoanApiController::class, 'storeApplication']);
        });
        Route::middleware(['can:loan_application.review'])->group(function () {
            Route::post('/loans/applications/{id}/review', [LoanApiController::class, 'reviewApplication']);
        });
        Route::middleware(['can:loan_settlement.request'])->group(function () {
            Route::post('/loans/accounts/{id}/settlements', [LoanApiController::class, 'processSettlement']);
        });
        Route::middleware(['can:loan.disburse'])->group(function () {
            Route::post('/loans/accounts/{id}/disburse', [LoanApiController::class, 'disburse']);
        });

        // Mobile Field Collections
        Route::middleware(['can:collection.collect'])->group(function () {
            Route::get('/collections/search', [CollectionApiController::class, 'search']);
            Route::post('/collections/submit-emi', [CollectionApiController::class, 'submitEmi']);
        });

        // Inventory & Procurement
        Route::middleware(['can:inventory.view'])->group(function () {
            Route::get('/inventory/categories', [InventoryApiController::class, 'categories']);
            Route::get('/inventory/brands', [InventoryApiController::class, 'brands']);
            Route::get('/inventory/products', [InventoryApiController::class, 'products']);
            Route::get('/inventory/stocks', [InventoryApiController::class, 'stocks']);
        });

        // Inventory Transfers
        Route::middleware(['can:inventory.view'])->group(function () {
            Route::get('/inventory/transfers', [InventoryTransferApiController::class, 'index']);
            Route::get('/inventory/transfers/{id}', [InventoryTransferApiController::class, 'show']);
        });
        Route::middleware(['can:inventory.transfer.create', 'idempotent'])->group(function () {
            Route::post('/inventory/transfers', [InventoryTransferApiController::class, 'store']);
        });
        Route::middleware(['can:inventory.transfer.approve'])->group(function () {
            Route::post('/inventory/transfers/{id}/approve', [InventoryTransferApiController::class, 'approve']);
        });
        Route::middleware(['can:inventory.transfer.reject'])->group(function () {
            Route::post('/inventory/transfers/{id}/reject', [InventoryTransferApiController::class, 'reject']);
        });
        Route::middleware(['can:inventory.transfer.dispatch'])->group(function () {
            Route::post('/inventory/transfers/{id}/dispatch', [InventoryTransferApiController::class, 'dispatchTransfer']);
        });
        Route::middleware(['can:inventory.transfer.receive'])->group(function () {
            Route::post('/inventory/transfers/{id}/receive', [InventoryTransferApiController::class, 'receive']);
        });

        // HRM, Leave & Payroll
        Route::get('/hrm/leave-types', [HrmApiController::class, 'leaveTypes']);
        Route::get('/hrm/leaves', [HrmApiController::class, 'leaves']);
        Route::get('/hrm/leaves/{id}', [HrmApiController::class, 'showLeave']);
        Route::post('/hrm/leaves', [HrmApiController::class, 'storeLeave']);
        Route::middleware(['can:hrm.leave.approve'])->group(function () {
            Route::post('/hrm/leaves/{id}/approve', [HrmApiController::class, 'approveLeave']);
            Route::post('/hrm/leaves/{id}/reject', [HrmApiController::class, 'rejectLeave']);
        });
        Route::get('/hrm/payslips', [HrmApiController::class, 'payslips']);
        Route::get('/hrm/payslips/{id}', [HrmApiController::class, 'showPayslip']);

        // Notifications
        Route::get('/notifications', [NotificationApiController::class, 'index']);
        Route::get('/notifications/unread-count', [NotificationApiController::class, 'unreadCount']);
        Route::post('/notifications/{id}/read', [NotificationApiController::class, 'markAsRead']);
        Route::post('/notifications/read-all', [NotificationApiController::class, 'markAllAsRead']);
        Route::delete('/notifications/{id}', [NotificationApiController::class, 'destroy']);

        // Reports
        Route::get('/reports', [ReportApiController::class, 'index']);
        Route::get('/reports/{category}/{type}', [ReportApiController::class, 'show']);
        Route::get('/reports/{category}/{type}/export', [ReportApiController::class, 'export']);

        // Cash Book
        Route::middleware(['can:cashbook.view'])->group(function () {
            Route::get('/cash-book', [CashBookApiController::class, 'show']);
        });
        Route::middleware(['can:cashbook.create_entry', 'idempotent'])->group(function () {
            Route::post('/cash-book/entries', [CashBookApiController::class, 'addEntry']);
            Route::post('/cash-book/reconcile', [CashBookApiController::class, 'saveDenomination']);
        });
        Route::middleware(['can:cashbook.close_register'])->group(function () {
            Route::post('/cash-book/close', [CashBookApiController::class, 'closeRegister']);
        });

        // Bank Deposits
        Route::middleware(['can:bank_deposit.view'])->group(function () {
            Route::get('/bank-deposits', [BankDepositApiController::class, 'index']);
            Route::get('/bank-deposits/{id}', [BankDepositApiController::class, 'show']);
        });
        Route::middleware(['can:bank_deposit.create'])->group(function () {
            Route::post('/bank-deposits', [BankDepositApiController::class, 'store']);
        });
        Route::middleware(['can:bank_deposit.approve'])->group(function () {
            Route::post('/bank-deposits/{id}/approve', [BankDepositApiController::class, 'approve']);
            Route::post('/bank-deposits/{id}/reject', [BankDepositApiController::class, 'reject']);
        });

        // Branch Expenses
        Route::middleware(['can:expense.view'])->group(function () {
            Route::get('/expenses/categories', [ExpenseApiController::class, 'categories']);
            Route::get('/expenses', [ExpenseApiController::class, 'index']);
            Route::get('/expenses/{id}', [ExpenseApiController::class, 'show']);
        });
        Route::middleware(['can:expense.create', 'idempotent'])->group(function () {
            Route::post('/expenses', [ExpenseApiController::class, 'store']);
            Route::post('/expenses/{id}/submit', [ExpenseApiController::class, 'submit']);
        });
        Route::middleware(['can:expense.approve'])->group(function () {
            Route::post('/expenses/{id}/approve', [ExpenseApiController::class, 'approve']);
            Route::post('/expenses/{id}/reject', [ExpenseApiController::class, 'reject']);
        });
        Route::middleware(['can:expense.pay'])->group(function () {
            Route::post('/expenses/{id}/pay', [ExpenseApiController::class, 'pay']);
        });

        // Staff Attendance & GPS Location
        Route::post('/attendance/check-in', [AttendanceApiController::class, 'checkIn']);
        Route::post('/attendance/check-out', [AttendanceApiController::class, 'checkOut']);

        // Location & GPS Field Tracking
        Route::post('/location/ping-in', [\App\Http\Controllers\Api\V1\LocationApiController::class, 'pingIn']);
        Route::post('/location/ping-out', [\App\Http\Controllers\Api\V1\LocationApiController::class, 'pingOut']);
        Route::post('/location/ping', [\App\Http\Controllers\Api\V1\LocationApiController::class, 'ping']);
        Route::post('/location/sync-pings', [\App\Http\Controllers\Api\V1\LocationApiController::class, 'syncBatchPings']);
        Route::get('/location/status', [\App\Http\Controllers\Api\V1\LocationApiController::class, 'status']);

        // Travel Allowance (TA) Claims
        Route::middleware(['can:ta_claims.view'])->group(function () {
            Route::get('/ta-claims', [\App\Http\Controllers\Api\V1\TravelAllowanceApiController::class, 'index']);
            Route::get('/ta-claims/eligible-distance', [\App\Http\Controllers\Api\V1\TravelAllowanceApiController::class, 'eligibleDistance']);
        });
        Route::middleware(['can:ta_claims.create', 'idempotent'])->group(function () {
            Route::post('/ta-claims', [\App\Http\Controllers\Api\V1\TravelAllowanceApiController::class, 'store']);
        });
        Route::middleware(['can:ta_claims.approve'])->group(function () {
            Route::post('/ta-claims/{id}/approve', [\App\Http\Controllers\Api\V1\TravelAllowanceApiController::class, 'approve']);
            Route::post('/ta-claims/{id}/reject', [\App\Http\Controllers\Api\V1\TravelAllowanceApiController::class, 'reject']);
        });
        Route::middleware(['can:ta_claims.pay'])->group(function () {
            Route::post('/ta-claims/{id}/pay', [\App\Http\Controllers\Api\V1\TravelAllowanceApiController::class, 'pay']);
        });

        // KYC & Document Uploads
        Route::middleware(['can:customer.kyc_upload'])->group(function () {
            Route::post('/kyc/upload', [KycApiController::class, 'upload']);
        });
        Route::middleware(['can:customer.kyc_view'])->group(function () {
            Route::get('/kyc/documents/{id}', [KycApiController::class, 'download']);
        });
        Route::middleware(['can:customer.verify_kyc'])->group(function () {
            Route::post('/kyc/documents/{id}/verify', [KycApiController::class, 'verify']);
            Route::delete('/kyc/documents/{id}', [KycApiController::class, 'destroy']);
        });
    });
});

