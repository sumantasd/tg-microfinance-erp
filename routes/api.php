<?php

use App\Http\Controllers\Api\V1\AttendanceApiController;
use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\CashBookApiController;
use App\Http\Controllers\Api\V1\CollectionApiController;
use App\Http\Controllers\Api\V1\CustomerApiController;
use App\Http\Controllers\Api\V1\DashboardApiController;
use App\Http\Controllers\Api\V1\GroupApiController;
use App\Http\Controllers\Api\V1\InventoryApiController;
use App\Http\Controllers\Api\V1\KycApiController;
use App\Http\Controllers\Api\V1\LoanApiController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Mobile API V1 Routes
|--------------------------------------------------------------------------
*/

Route::prefix('v1')->group(function () {

    // Public Auth Endpoints
    Route::post('/auth/login', [AuthController::class, 'login']);

    // Protected API Endpoints (Sanctum Auth)
    Route::middleware(['auth:sanctum'])->group(function () {

        // Auth
        Route::post('/auth/logout', [AuthController::class, 'logout']);
        Route::get('/auth/me', [AuthController::class, 'me']);

        // Dashboard Stats
        Route::get('/dashboard/stats', [DashboardApiController::class, 'stats']);

        // Customers
        Route::middleware(['can:customer.view'])->group(function () {
            Route::get('/customers', [CustomerApiController::class, 'index']);
            Route::get('/customers/{id}', [CustomerApiController::class, 'show']);
        });
        Route::middleware(['can:customer.create'])->group(function () {
            Route::post('/customers', [CustomerApiController::class, 'store']);
        });

        // Customer Groups
        Route::middleware(['can:group.view'])->group(function () {
            Route::get('/groups', [GroupApiController::class, 'index']);
            Route::get('/groups/{id}', [GroupApiController::class, 'show']);
        });

        // Loans
        Route::middleware(['can:loan.view'])->group(function () {
            Route::get('/loans/schemes', [LoanApiController::class, 'schemes']);
            Route::get('/loans/applications', [LoanApiController::class, 'applications']);
            Route::get('/loans/accounts', [LoanApiController::class, 'accounts']);
            Route::get('/loans/accounts/{id}', [LoanApiController::class, 'showAccount']);
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

        // Cash Book
        Route::middleware(['can:cashbook.view'])->group(function () {
            Route::get('/cash-book', [CashBookApiController::class, 'show']);
        });
        Route::middleware(['can:cashbook.create_entry'])->group(function () {
            Route::post('/cash-book/entries', [CashBookApiController::class, 'addEntry']);
            Route::post('/cash-book/reconcile', [CashBookApiController::class, 'saveDenomination']);
        });
        Route::middleware(['can:cashbook.close_register'])->group(function () {
            Route::post('/cash-book/close', [CashBookApiController::class, 'closeRegister']);
        });

        // Staff Attendance & GPS Location
        Route::post('/attendance/check-in', [AttendanceApiController::class, 'checkIn']);
        Route::post('/attendance/check-out', [AttendanceApiController::class, 'checkOut']);

        // KYC & Document Uploads
        Route::middleware(['can:customer.kyc_upload'])->group(function () {
            Route::post('/kyc/upload', [KycApiController::class, 'upload']);
        });
        Route::middleware(['can:customer.kyc_view'])->group(function () {
            Route::get('/kyc/documents/{id}', [KycApiController::class, 'download']);
        });
    });
});
