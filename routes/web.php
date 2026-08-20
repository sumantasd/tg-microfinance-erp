<?php

use App\Http\Controllers\Admin\Cms\BannerController;
use App\Http\Controllers\Admin\Cms\CareerController;
use App\Http\Controllers\Admin\Cms\CmsLoanProductController;
use App\Http\Controllers\Admin\Cms\CmsSavingsProductController;
use App\Http\Controllers\Admin\Cms\CmsServiceController;
use App\Http\Controllers\Admin\Cms\ContactInquiryController;
use App\Http\Controllers\Admin\Cms\DownloadController;
use App\Http\Controllers\Admin\Cms\FaqController;
use App\Http\Controllers\Admin\Cms\FooterSettingController;
use App\Http\Controllers\Admin\Cms\GalleryController;
use App\Http\Controllers\Admin\Cms\HomepageSectionController;
use App\Http\Controllers\Admin\Cms\InterestRateController;
use App\Http\Controllers\Admin\Cms\NewsController;
use App\Http\Controllers\Admin\Cms\PageController;
use App\Http\Controllers\Admin\Cms\SeoSettingController;
use App\Http\Controllers\Admin\Cms\TeamMemberController;
use App\Http\Controllers\Admin\Cms\WebsiteSettingController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\GlobalSearchController;
use App\Http\Controllers\Admin\AccountingDashboardController;
use App\Http\Controllers\Admin\BankAccountController;
use App\Http\Controllers\Admin\ChartOfAccountController;
use App\Http\Controllers\Admin\VoucherController;
use App\Http\Controllers\Admin\ReportsController;
use App\Http\Controllers\Admin\BranchController;
use App\Http\Controllers\Admin\CompanyController;
use App\Http\Controllers\Admin\CustomerController;
use App\Http\Controllers\Admin\CustomerGroupController;
use App\Http\Controllers\Admin\CustomerGuarantorController;
use App\Http\Controllers\Admin\CustomerKycController;
use App\Http\Controllers\Admin\CustomerNomineeController;
use App\Http\Controllers\Admin\DepartmentController;
use App\Http\Controllers\Admin\DesignationController;
use App\Http\Controllers\Admin\EmployeeController;
use App\Http\Controllers\Admin\InventoryController;
use App\Http\Controllers\Admin\InventoryTransferController;
use App\Http\Controllers\Admin\EmiCollectionController;
use App\Http\Controllers\Admin\OverdueController;
use App\Http\Controllers\Admin\LoanPenaltyController;
use App\Http\Controllers\Admin\LoanAccountController;
use App\Http\Controllers\Admin\LoanSettlementController;
use App\Http\Controllers\Admin\LoanApplicationController;
use App\Http\Controllers\Admin\LoanSchemeController;
use App\Http\Controllers\Admin\ProductBrandController;
use App\Http\Controllers\Admin\ProductCategoryController;
use App\Http\Controllers\Admin\ProductPurchaseController;
use App\Http\Controllers\Admin\SupplierController;
use App\Http\Controllers\Admin\SupplierPaymentController;
use App\Http\Controllers\Admin\ProductController as AdminProductController;
use App\Http\Controllers\Admin\ProfileController;
use App\Http\Controllers\Admin\System\PermissionController;
use App\Http\Controllers\Admin\System\RoleController;
use App\Http\Controllers\Admin\System\UserController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Public\AboutController;
use App\Http\Controllers\Public\ContactController;
use App\Http\Controllers\Public\HomeController;
use App\Http\Controllers\Public\ProductController;
use App\Http\Controllers\Public\ResourceController;
use App\Http\Middleware\EnsureAdminAuthenticated;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| TG Microfinance ERP - Public Website Routes
|--------------------------------------------------------------------------
*/

Route::get('/', [HomeController::class, 'index'])->name('public.home');

// About Routes
Route::prefix('about')->group(function () {
    Route::get('/', [AboutController::class, 'index'])->name('public.about.index');
    Route::get('/mission', [AboutController::class, 'mission'])->name('public.about.mission');
    Route::get('/vision', [AboutController::class, 'vision'])->name('public.about.vision');
    Route::get('/board-of-directors', [AboutController::class, 'boardOfDirectors'])->name('public.about.board-of-directors');
    Route::get('/management-team', [AboutController::class, 'managementTeam'])->name('public.about.management-team');
});

// Products Routes
Route::prefix('products')->group(function () {
    Route::get('/loan', [ProductController::class, 'loanProducts'])->name('public.products.loan');
    Route::get('/savings', [ProductController::class, 'savingsProducts'])->name('public.products.savings');
    Route::get('/interest-rates', [ProductController::class, 'interestRates'])->name('public.products.interest-rates');
});

// Services Routes
Route::prefix('services')->group(function () {
    Route::get('/', [ProductController::class, 'servicesIndex'])->name('public.services.index');
    Route::get('/digital-banking', [ProductController::class, 'serviceDigitalBanking'])->name('public.services.digital-banking');
    Route::get('/collection-services', [ProductController::class, 'serviceCollectionServices'])->name('public.services.collection-services');
    Route::get('/financial-advisory', [ProductController::class, 'serviceFinancialAdvisory'])->name('public.services.financial-advisory');
    Route::get('/{slug}', [ProductController::class, 'serviceShow'])->name('public.services.show');
});

// Resources Routes
Route::prefix('resources')->group(function () {
    Route::get('/news', [ResourceController::class, 'news'])->name('public.resources.news');
    Route::get('/news/{slug}', [ResourceController::class, 'newsShow'])->name('public.resources.news.show');
    Route::get('/gallery', [ResourceController::class, 'gallery'])->name('public.resources.gallery');
    Route::get('/downloads', [ResourceController::class, 'downloads'])->name('public.resources.downloads');
    Route::get('/downloads/{id}/download', [ResourceController::class, 'downloadFile'])->name('public.resources.downloads.file');
    Route::get('/faq', [ResourceController::class, 'faq'])->name('public.resources.faq');
    Route::get('/career', [ResourceController::class, 'career'])->name('public.resources.career');
});

// General Public Routes
Route::get('/branches', function () { return view('public.branches'); })->name('public.branches');
Route::get('/contact', [ContactController::class, 'show'])->name('public.contact');
Route::post('/contact', [ContactController::class, 'submit'])->name('public.contact.submit');
Route::get('/apply-loan', function () { return view('public.apply-loan'); })->name('public.apply-loan');

/*
|--------------------------------------------------------------------------
| TG Microfinance ERP - Staff Authentication Routes
|--------------------------------------------------------------------------
*/

Route::get('/login', [LoginController::class, 'showLoginForm'])->name('login');
Route::post('/login', [LoginController::class, 'login']);
Route::post('/logout', [LoginController::class, 'logout'])->name('logout');

Route::get('/forgot-password', function () { return view('auth.forgot-password'); })->name('forgot-password');
Route::get('/reset-password', function () { return view('auth.reset-password'); })->name('reset-password');

/*
|--------------------------------------------------------------------------
| TG Microfinance ERP - Admin Panel Foundation & RBAC Routes (Protected)
|--------------------------------------------------------------------------
*/

Route::middleware([EnsureAdminAuthenticated::class])->prefix('admin')->group(function () {
    Route::get('/', [DashboardController::class, 'index'])->name('admin.dashboard');
    Route::get('/dashboard', [DashboardController::class, 'index']);
    Route::get('/search', [GlobalSearchController::class, 'search'])->name('admin.search');

    // Profile Management Routes
    Route::get('/profile', [ProfileController::class, 'show'])->name('admin.profile.show');
    Route::put('/profile', [ProfileController::class, 'updateProfile'])->name('admin.profile.update');
    Route::put('/profile/password', [ProfileController::class, 'changePassword'])->name('admin.profile.password');

    // System Modules - Real Functional RBAC Routes
    Route::prefix('system')->group(function () {
        // User Management CRUD
        Route::middleware('can:users.view')->group(function () {
            Route::get('/users', [UserController::class, 'index'])->name('admin.system.users.index');
            Route::get('/users/create', [UserController::class, 'create'])->name('admin.system.users.create')->middleware('can:users.create');
            Route::post('/users', [UserController::class, 'store'])->name('admin.system.users.store')->middleware('can:users.create');
            Route::get('/users/{user}/edit', [UserController::class, 'edit'])->name('admin.system.users.edit')->middleware('can:users.edit');
            Route::put('/users/{user}', [UserController::class, 'update'])->name('admin.system.users.update')->middleware('can:users.edit');
            Route::patch('/users/{user}/toggle-status', [UserController::class, 'toggleStatus'])->name('admin.system.users.toggle-status')->middleware('can:users.toggle_status');
            Route::delete('/users/{user}', [UserController::class, 'destroy'])->name('admin.system.users.destroy')->middleware('can:users.delete');
        });

        // Role Management CRUD
        Route::middleware('can:roles.view')->group(function () {
            Route::get('/roles', [RoleController::class, 'index'])->name('admin.system.roles.index');
            Route::get('/roles/create', [RoleController::class, 'create'])->name('admin.system.roles.create')->middleware('can:roles.create');
            Route::post('/roles', [RoleController::class, 'store'])->name('admin.system.roles.store')->middleware('can:roles.create');
            Route::get('/roles/{role}/edit', [RoleController::class, 'edit'])->name('admin.system.roles.edit')->middleware('can:roles.edit');
            Route::put('/roles/{role}', [RoleController::class, 'update'])->name('admin.system.roles.update')->middleware('can:roles.edit');
            Route::delete('/roles/{role}', [RoleController::class, 'destroy'])->name('admin.system.roles.destroy')->middleware('can:roles.delete');
        });

        // Permissions Matrix
        Route::middleware('can:permissions.view')->group(function () {
            Route::get('/permissions', [PermissionController::class, 'index'])->name('admin.system.permissions.index');
            Route::post('/permissions', [PermissionController::class, 'store'])->name('admin.system.permissions.store');
            Route::delete('/permissions/{permission}', [PermissionController::class, 'destroy'])->name('admin.system.permissions.destroy');
        });

        // System Subpages & Settings
        Route::middleware('can:settings.view')->group(function () {
            Route::get('/settings', [\App\Http\Controllers\Admin\SystemSettingController::class, 'index'])->name('admin.system.settings.index');
            Route::put('/settings/loan-charges', [\App\Http\Controllers\Admin\SystemSettingController::class, 'updateLoanCharges'])->name('admin.system.settings.update-loan-charges');
            Route::get('/media', function () { return view('admin.placeholders.module', ['moduleTitle' => 'Media Library', 'moduleSlug' => 'system/media']); });
            Route::get('/notifications', function () { return view('admin.placeholders.module', ['moduleTitle' => 'System Notifications', 'moduleSlug' => 'system/notifications']); });
            Route::get('/audit-logs', function () { return view('admin.placeholders.module', ['moduleTitle' => 'Audit Logs', 'moduleSlug' => 'system/audit-logs']); });
            Route::get('/backup', function () { return view('admin.placeholders.module', ['moduleTitle' => 'Database Backup', 'moduleSlug' => 'system/backup']); });
        });
    });

    // ERP Core Modules - Real Functional Company & Branch Routes
    Route::middleware('can:company.view')->group(function () {
        Route::get('/company', [CompanyController::class, 'index'])->name('admin.company.index');
        Route::get('/company/create', [CompanyController::class, 'create'])->name('admin.company.create')->middleware('can:company.create');
        Route::post('/company', [CompanyController::class, 'store'])->name('admin.company.store')->middleware('can:company.create');
        Route::get('/company/{company}', [CompanyController::class, 'show'])->name('admin.company.show');
        Route::get('/company/{company}/edit', [CompanyController::class, 'edit'])->name('admin.company.edit')->middleware('can:company.edit');
        Route::put('/company/{company}', [CompanyController::class, 'update'])->name('admin.company.update')->middleware('can:company.edit');
        Route::patch('/company/{company}/toggle-status', [CompanyController::class, 'toggleStatus'])->name('admin.company.toggle-status')->middleware('can:company.toggle_status');
        Route::delete('/company/{company}', [CompanyController::class, 'destroy'])->name('admin.company.destroy')->middleware('can:company.delete');
        Route::post('/company/{id}/restore', [CompanyController::class, 'restore'])->name('admin.company.restore')->middleware('can:company.restore');
    });

    Route::middleware('can:branch.view')->group(function () {
        Route::get('/branch', [BranchController::class, 'index'])->name('admin.branch.index');
        Route::get('/branch/create', [BranchController::class, 'create'])->name('admin.branch.create')->middleware('can:branch.create');
        Route::post('/branch', [BranchController::class, 'store'])->name('admin.branch.store')->middleware('can:branch.create');
        Route::get('/branch/{branch}', [BranchController::class, 'show'])->name('admin.branch.show');
        Route::get('/branch/{branch}/edit', [BranchController::class, 'edit'])->name('admin.branch.edit')->middleware('can:branch.edit');
        Route::put('/branch/{branch}', [BranchController::class, 'update'])->name('admin.branch.update')->middleware('can:branch.edit');
        Route::patch('/branch/{branch}/toggle-status', [BranchController::class, 'toggleStatus'])->name('admin.branch.toggle-status')->middleware('can:branch.toggle_status');
        Route::delete('/branch/{branch}', [BranchController::class, 'destroy'])->name('admin.branch.destroy')->middleware('can:branch.delete');
        Route::post('/branch/{id}/restore', [BranchController::class, 'restore'])->name('admin.branch.restore')->middleware('can:branch.restore');
    });

    // Phase 3: HRM Foundation Routes
    Route::middleware('can:department.view')->group(function () {
        Route::get('/department', [DepartmentController::class, 'index'])->name('admin.department.index');
        Route::get('/department/create', [DepartmentController::class, 'create'])->name('admin.department.create')->middleware('can:department.create');
        Route::post('/department', [DepartmentController::class, 'store'])->name('admin.department.store')->middleware('can:department.create');
        Route::get('/department/{department}', [DepartmentController::class, 'show'])->name('admin.department.show');
        Route::get('/department/{department}/edit', [DepartmentController::class, 'edit'])->name('admin.department.edit')->middleware('can:department.edit');
        Route::put('/department/{department}', [DepartmentController::class, 'update'])->name('admin.department.update')->middleware('can:department.edit');
        Route::patch('/department/{department}/toggle-status', [DepartmentController::class, 'toggleStatus'])->name('admin.department.toggle-status')->middleware('can:department.toggle_status');
        Route::delete('/department/{department}', [DepartmentController::class, 'destroy'])->name('admin.department.destroy')->middleware('can:department.delete');
        Route::post('/department/{id}/restore', [DepartmentController::class, 'restore'])->name('admin.department.restore')->middleware('can:department.restore');
    });

    Route::middleware('can:designation.view')->group(function () {
        Route::get('/designation', [DesignationController::class, 'index'])->name('admin.designation.index');
        Route::get('/designation/create', [DesignationController::class, 'create'])->name('admin.designation.create')->middleware('can:designation.create');
        Route::post('/designation', [DesignationController::class, 'store'])->name('admin.designation.store')->middleware('can:designation.create');
        Route::get('/designation/{designation}', [DesignationController::class, 'show'])->name('admin.designation.show');
        Route::get('/designation/{designation}/edit', [DesignationController::class, 'edit'])->name('admin.designation.edit')->middleware('can:designation.edit');
        Route::put('/designation/{designation}', [DesignationController::class, 'update'])->name('admin.designation.update')->middleware('can:designation.edit');
        Route::patch('/designation/{designation}/toggle-status', [DesignationController::class, 'toggleStatus'])->name('admin.designation.toggle-status')->middleware('can:designation.toggle_status');
        Route::delete('/designation/{designation}', [DesignationController::class, 'destroy'])->name('admin.designation.destroy')->middleware('can:designation.delete');
        Route::post('/designation/{id}/restore', [DesignationController::class, 'restore'])->name('admin.designation.restore')->middleware('can:designation.restore');
    });

    Route::middleware('can:employee.view')->group(function () {
        Route::get('/employee', [EmployeeController::class, 'index'])->name('admin.employee.index');
        Route::get('/employee/create', [EmployeeController::class, 'create'])->name('admin.employee.create')->middleware('can:employee.create');
        Route::post('/employee', [EmployeeController::class, 'store'])->name('admin.employee.store')->middleware('can:employee.create');
        Route::get('/employee/{employee}', [EmployeeController::class, 'show'])->name('admin.employee.show');
        Route::get('/employee/{employee}/edit', [EmployeeController::class, 'edit'])->name('admin.employee.edit')->middleware('can:employee.edit');
        Route::put('/employee/{employee}', [EmployeeController::class, 'update'])->name('admin.employee.update')->middleware('can:employee.edit');
        Route::patch('/employee/{employee}/toggle-status', [EmployeeController::class, 'toggleStatus'])->name('admin.employee.toggle-status')->middleware('can:employee.toggle_status');
        Route::delete('/employee/{employee}', [EmployeeController::class, 'destroy'])->name('admin.employee.destroy')->middleware('can:employee.delete');
        Route::post('/employee/{id}/restore', [EmployeeController::class, 'restore'])->name('admin.employee.restore')->middleware('can:employee.restore');
        Route::delete('/employee/document/{document}', [EmployeeController::class, 'destroyDocument'])->name('admin.employee.document.destroy')->middleware('can:employee.edit');
    });

    // Essential HRM Modules Routes
    Route::middleware('can:attendance.view')->group(function () {
        Route::get('/hrm/attendance', [\App\Http\Controllers\Admin\AttendanceController::class, 'index'])->name('admin.hrm.attendance.index');
        Route::post('/hrm/attendance', [\App\Http\Controllers\Admin\AttendanceController::class, 'store'])->name('admin.hrm.attendance.store')->middleware('can:attendance.create');
    });

    Route::middleware('can:leave.view')->group(function () {
        Route::get('/hrm/leave', [\App\Http\Controllers\Admin\LeaveController::class, 'index'])->name('admin.hrm.leave.index');
        Route::post('/hrm/leave', [\App\Http\Controllers\Admin\LeaveController::class, 'store'])->name('admin.hrm.leave.store')->middleware('can:leave.create');
        Route::post('/hrm/leave/{leave}/approve', [\App\Http\Controllers\Admin\LeaveController::class, 'approve'])->name('admin.hrm.leave.approve')->middleware('can:leave.approve');
        Route::post('/hrm/leave/{leave}/reject', [\App\Http\Controllers\Admin\LeaveController::class, 'reject'])->name('admin.hrm.leave.reject')->middleware('can:leave.reject');
    });

    Route::middleware('can:payroll.view')->group(function () {
        Route::get('/hrm/payroll', [\App\Http\Controllers\Admin\PayrollController::class, 'index'])->name('admin.hrm.payroll.index');
        Route::post('/hrm/payroll', [\App\Http\Controllers\Admin\PayrollController::class, 'store'])->name('admin.hrm.payroll.store')->middleware('can:payroll.process');
        Route::get('/hrm/payroll/{id}', [\App\Http\Controllers\Admin\PayrollController::class, 'show'])->name('admin.hrm.payroll.show');
        Route::post('/hrm/payroll/{id}/disburse', [\App\Http\Controllers\Admin\PayrollController::class, 'disburse'])->name('admin.hrm.payroll.disburse')->middleware('can:payroll.disburse');
        Route::get('/hrm/payroll/slip/{uuid}', [\App\Http\Controllers\Admin\PayrollController::class, 'salarySlip'])->name('admin.hrm.payroll.slip');
    });

    Route::middleware('can:hr_letter.view')->group(function () {
        Route::get('/hrm/letters', [\App\Http\Controllers\Admin\HrLetterController::class, 'index'])->name('admin.hrm.letters.index');
        Route::get('/hrm/letters/{employee}/generate', [\App\Http\Controllers\Admin\HrLetterController::class, 'generate'])->name('admin.hrm.letters.generate')->middleware('can:hr_letter.generate');
        Route::get('/hrm/letters/{employee}/id-card', [\App\Http\Controllers\Admin\HrLetterController::class, 'idCard'])->name('admin.hrm.letters.id-card');
    });

    Route::get('/hrm/reports', [\App\Http\Controllers\Admin\HrReportController::class, 'index'])->name('admin.hrm.reports.index')->middleware('can:hr_reports.view');

    // Module 6: Customer & Member Management Routes
    Route::middleware('can:customer.view')->group(function () {
        Route::get('/customer', [CustomerController::class, 'index'])->name('admin.customer.index');
        Route::get('/customer/create', [CustomerController::class, 'create'])->name('admin.customer.create')->middleware('can:customer.create');
        Route::post('/customer', [CustomerController::class, 'store'])->name('admin.customer.store')->middleware('can:customer.create');
        Route::get('/customer/{customer}', [CustomerController::class, 'show'])->name('admin.customer.show');
        Route::get('/customer/{customer}/edit', [CustomerController::class, 'edit'])->name('admin.customer.edit')->middleware('can:customer.edit');
        Route::put('/customer/{customer}', [CustomerController::class, 'update'])->name('admin.customer.update')->middleware('can:customer.edit');
        Route::patch('/customer/{customer}/toggle-status', [CustomerController::class, 'toggleStatus'])->name('admin.customer.toggle-status')->middleware('can:customer.change_status');
        Route::delete('/customer/{customer}', [CustomerController::class, 'destroy'])->name('admin.customer.destroy')->middleware('can:customer.delete');
        Route::post('/customer/{id}/restore', [CustomerController::class, 'restore'])->name('admin.customer.restore')->middleware('can:customer.restore');

        // Customer KYC Routes
        Route::post('/customer/{customer}/kyc', [CustomerKycController::class, 'store'])->name('admin.customer.kyc.store')->middleware('can:customer.verify_kyc');
        Route::get('/customer/kyc/{kyc}/download', [CustomerKycController::class, 'download'])->name('admin.customer.kyc.download');
        Route::post('/customer/kyc/{kyc}/verify', [CustomerKycController::class, 'verify'])->name('admin.customer.kyc.verify')->middleware('can:customer.verify_kyc');
        Route::delete('/customer/kyc/{kyc}', [CustomerKycController::class, 'destroy'])->name('admin.customer.kyc.destroy')->middleware('can:customer.verify_kyc');

        // Customer Guarantor Routes
        Route::post('/customer/{customer}/guarantor', [CustomerGuarantorController::class, 'store'])->name('admin.customer.guarantor.store')->middleware('can:customer.manage_guarantor');
        Route::get('/customer/guarantor/{guarantor}/download-kyc', [CustomerGuarantorController::class, 'downloadKyc'])->name('admin.customer.guarantor.download-kyc');
        Route::delete('/customer/guarantor/{guarantor}', [CustomerGuarantorController::class, 'destroy'])->name('admin.customer.guarantor.destroy')->middleware('can:customer.manage_guarantor');

        // Customer Nominee Routes
        Route::post('/customer/{customer}/nominee', [CustomerNomineeController::class, 'store'])->name('admin.customer.nominee.store')->middleware('can:customer.manage_nominee');
        Route::delete('/customer/nominee/{nominee}', [CustomerNomineeController::class, 'destroy'])->name('admin.customer.nominee.destroy')->middleware('can:customer.manage_nominee');
    });

    // Customer Group Management Routes
    Route::middleware('can:group.view')->group(function () {
        Route::get('/customer-group', [CustomerGroupController::class, 'index'])->name('admin.customer-group.index');
        Route::get('/customer-group/create', [CustomerGroupController::class, 'create'])->name('admin.customer-group.create')->middleware('can:group.create');
        Route::post('/customer-group', [CustomerGroupController::class, 'store'])->name('admin.customer-group.store')->middleware('can:group.create');
        Route::get('/customer-group/{group}', [CustomerGroupController::class, 'show'])->name('admin.customer-group.show');
        Route::get('/customer-group/{group}/edit', [CustomerGroupController::class, 'edit'])->name('admin.customer-group.edit')->middleware('can:group.edit');
        Route::put('/customer-group/{group}', [CustomerGroupController::class, 'update'])->name('admin.customer-group.update')->middleware('can:group.edit');
        Route::delete('/customer-group/{group}', [CustomerGroupController::class, 'destroy'])->name('admin.customer-group.destroy')->middleware('can:group.delete');
        Route::patch('/customer-group/{group}/toggle-status', [CustomerGroupController::class, 'toggleStatus'])->name('admin.customer-group.toggle-status')->middleware('can:group.change_status');
        Route::post('/customer-group/{group}/member', [CustomerGroupController::class, 'addMember'])->name('admin.customer-group.member.store')->middleware('can:group.manage_members');
        Route::delete('/customer-group/{group}/member/{customer}', [CustomerGroupController::class, 'removeMember'])->name('admin.customer-group.member.destroy')->middleware('can:group.manage_members');
        Route::post('/customer-group/{group}/assign-leader', [CustomerGroupController::class, 'assignLeader'])->name('admin.customer-group.assign-leader')->middleware('can:group.assign_leader');
    });

    // Module 7 — Phase 7.1 Routes (Loan Schemes, Product Catalog, Generic Inventory)
    Route::resource('loan-scheme', LoanSchemeController::class, ['as' => 'admin'])->middleware('can:loan_scheme.view');
    Route::resource('product-brand', ProductBrandController::class, ['as' => 'admin'])->except(['show'])->middleware('can:product_brand.view');
    Route::resource('product-category', ProductCategoryController::class, ['as' => 'admin'])->except(['show'])->middleware('can:product_category.view');
    Route::resource('product', AdminProductController::class, ['as' => 'admin'])->middleware('can:product.view');
    
    Route::middleware('can:inventory.view')->group(function () {
        Route::get('/inventory', [InventoryController::class, 'index'])->name('admin.inventory.index');
        Route::get('/inventory/movements', [InventoryController::class, 'movements'])->name('admin.inventory.movements');
        Route::post('/inventory/restock', [InventoryController::class, 'restock'])->name('admin.inventory.restock')->middleware('can:inventory.restock');
        Route::post('/inventory/adjust', [InventoryController::class, 'adjust'])->name('admin.inventory.adjust')->middleware('can:inventory.adjust');
    });

    // Branch-to-Branch Inventory Transfer Routes
    Route::middleware('can:inventory.transfer.view')->group(function () {
        Route::get('/inventory/transfers', [InventoryTransferController::class, 'index'])->name('admin.inventory-transfer.index');
        Route::get('/inventory/transfers/create', [InventoryTransferController::class, 'create'])->name('admin.inventory-transfer.create')->middleware('can:inventory.transfer.create');
        Route::post('/inventory/transfers', [InventoryTransferController::class, 'store'])->name('admin.inventory-transfer.store')->middleware('can:inventory.transfer.create');
        Route::get('/inventory/transfers/{inventoryTransfer}', [InventoryTransferController::class, 'show'])->name('admin.inventory-transfer.show');
        Route::post('/inventory/transfers/{inventoryTransfer}/request', [InventoryTransferController::class, 'requestTransfer'])->name('admin.inventory-transfer.request')->middleware('can:inventory.transfer.create');
        Route::post('/inventory/transfers/{inventoryTransfer}/approve', [InventoryTransferController::class, 'approve'])->name('admin.inventory-transfer.approve')->middleware('can:inventory.transfer.approve');
        Route::post('/inventory/transfers/{inventoryTransfer}/reject', [InventoryTransferController::class, 'reject'])->name('admin.inventory-transfer.reject')->middleware('can:inventory.transfer.reject');
        Route::post('/inventory/transfers/{inventoryTransfer}/dispatch', [InventoryTransferController::class, 'dispatchTransfer'])->name('admin.inventory-transfer.dispatch')->middleware('can:inventory.transfer.dispatch');
        Route::post('/inventory/transfers/{inventoryTransfer}/receive', [InventoryTransferController::class, 'receive'])->name('admin.inventory-transfer.receive')->middleware('can:inventory.transfer.receive');
        Route::post('/inventory/transfers/{inventoryTransfer}/cancel', [InventoryTransferController::class, 'cancel'])->name('admin.inventory-transfer.cancel')->middleware('can:inventory.transfer.cancel');
    });

    // Product Purchase / Procurement Management Routes
    Route::middleware('can:purchase.view')->group(function () {
        Route::resource('inventory/purchases', ProductPurchaseController::class)->parameters(['purchases' => 'purchase'])->names('admin.product-purchase');
        Route::post('/inventory/purchases/{purchase}/confirm', [ProductPurchaseController::class, 'confirm'])->name('admin.product-purchase.confirm')->middleware('can:purchase.confirm');
        Route::post('/inventory/purchases/{purchase}/receive', [ProductPurchaseController::class, 'receive'])->name('admin.product-purchase.receive')->middleware('can:purchase.receive');
        Route::post('/inventory/purchases/{purchase}/cancel', [ProductPurchaseController::class, 'cancel'])->name('admin.product-purchase.cancel')->middleware('can:purchase.cancel');
    });

    // Supplier / Vendor Management Routes
    Route::middleware('can:supplier.view')->group(function () {
        Route::resource('suppliers', SupplierController::class)->names('admin.suppliers');
        Route::post('/supplier-payments', [SupplierPaymentController::class, 'store'])->name('admin.supplier-payments.store')->middleware('can:supplier.payments');
        Route::post('/supplier-payments/{payment}/allocate', [SupplierPaymentController::class, 'allocate'])->name('admin.supplier-payments.allocate')->middleware('can:supplier.payments');
    });

    // Module 7 — Phase 7.2 Routes (Loan Applications & Approvals)
    Route::middleware('can:loan_application.view')->group(function () {
        Route::get('/loan-application/ajax/brands-by-category', [LoanApplicationController::class, 'getBrandsByCategory'])->name('admin.loan-application.ajax.brands-by-category');
        Route::get('/loan-application/ajax/search-products', [LoanApplicationController::class, 'searchProducts'])->name('admin.loan-application.ajax.search-products');
        Route::resource('loan-application', LoanApplicationController::class, ['as' => 'admin']);
        Route::post('/loan-application/{loanApplication}/submit', [LoanApplicationController::class, 'submitApplication'])->name('admin.loan-application.submit')->middleware('can:loan_application.submit');
        Route::post('/loan-application/{loanApplication}/start-review', [LoanApplicationController::class, 'startReview'])->name('admin.loan-application.start-review')->middleware('can:loan_application.review');
        Route::post('/loan-application/{loanApplication}/approve', [LoanApplicationController::class, 'approve'])->name('admin.loan-application.approve')->middleware('can:loan_application.approve');
        Route::post('/loan-application/{loanApplication}/reject', [LoanApplicationController::class, 'reject'])->name('admin.loan-application.reject')->middleware('can:loan_application.reject');
        Route::post('/loan-application/{loanApplication}/cancel', [LoanApplicationController::class, 'cancel'])->name('admin.loan-application.cancel')->middleware('can:loan_application.cancel');
    });

    // Module 7 — Phase 7.3 Routes (Loan Accounts, Sanction, Down Payment, Disbursement & EMI Schedule)
    Route::middleware('can:loan.view')->group(function () {
        Route::resource('loan-account', LoanAccountController::class, ['as' => 'admin'])->only(['index', 'show']);
        Route::post('/loan-account/sanction', [LoanAccountController::class, 'sanction'])->name('admin.loan-account.sanction')->middleware('can:loan.sanction');
        Route::get('/loan-account/{loanAccount}/statement', [LoanAccountController::class, 'statement'])->name('admin.loan-account.statement');
        Route::post('/loan-account/{loanAccount}/upfront-payment', [LoanAccountController::class, 'recordUpfrontPayment'])->name('admin.loan-account.record-upfront-payment')->middleware('can:loan.sanction');
        Route::post('/loan-account/{loanAccount}/down-payment', [LoanAccountController::class, 'recordDownPayment'])->name('admin.loan-account.record-down-payment')->middleware('can:loan.record_down_payment');
        Route::post('/loan-account/{loanAccount}/disburse-cash', [LoanAccountController::class, 'disburseCash'])->name('admin.loan-account.disburse-cash')->middleware('can:loan.disburse');
        Route::post('/loan-account/{loanAccount}/issue-product', [LoanAccountController::class, 'issueProduct'])->name('admin.loan-account.issue-product')->middleware('can:loan.issue_product');
        Route::post('/loan-account/{loanAccount}/repayment', [LoanAccountController::class, 'recordRepayment'])->name('admin.loan-account.record-repayment')->middleware('can:loan.record_repayment');
        Route::get('/loan-account/{loanAccount}/noc', [LoanSettlementController::class, 'noc'])->name('admin.loan-account.noc')->middleware('can:loan_closure.certificate');
    });

    // Loan Settlement, Foreclosure, OTS & Early Closure Management Routes
    Route::prefix('loan-settlement')->name('admin.loan-settlement.')->group(function () {
        Route::get('/', [LoanSettlementController::class, 'index'])->name('index')->middleware('can:loan_closure.view');
        Route::get('/quote/{loanAccount}', [LoanSettlementController::class, 'quote'])->name('quote')->middleware('can:loan_closure.calculate');
        Route::post('/foreclose/{loanAccount}', [LoanSettlementController::class, 'foreclose'])->name('foreclose')->middleware('can:loan_foreclosure.process');
        Route::post('/request-ots/{loanAccount}', [LoanSettlementController::class, 'requestOts'])->name('request-ots')->middleware('can:loan_settlement.request');
        Route::post('/request-write-off/{loanAccount}', [LoanSettlementController::class, 'requestWriteOff'])->name('request-write-off')->middleware('can:loan_write_off.request');
        Route::get('/{settlementRequest}', [LoanSettlementController::class, 'show'])->name('show')->middleware('can:loan_closure.view');
        Route::post('/{settlementRequest}/approve', [LoanSettlementController::class, 'approve'])->name('approve')->middleware('can:loan_settlement.approve');
        Route::post('/{settlementRequest}/reject', [LoanSettlementController::class, 'reject'])->name('reject')->middleware('can:loan_settlement.approve');
        Route::post('/{settlementRequest}/execute', [LoanSettlementController::class, 'execute'])->name('execute')->middleware('can:loan_foreclosure.process');
    });
    
    // Dedicated Repayment & EMI Collection Module Routes
    Route::middleware('can:collection.view')->group(function () {
        Route::get('/emi-collection', [EmiCollectionController::class, 'index'])->name('admin.emi-collection.index');
        Route::get('/emi-collection/receipt/{repayment}', [EmiCollectionController::class, 'receipt'])->name('admin.emi-collection.receipt');
        Route::get('/emi-collection/thermal-receipt/{repayment}', [EmiCollectionController::class, 'thermalReceipt'])->name('admin.emi-collection.thermal-receipt');
    });

    // Overdue & DPD Management Routes
    Route::prefix('overdue')->name('admin.overdue.')->middleware('can:overdue.view')->group(function () {
        Route::get('/', [OverdueController::class, 'dashboard'])->name('dashboard');
        Route::get('/loans', [OverdueController::class, 'loans'])->name('loans');
        Route::get('/installments', [OverdueController::class, 'installments'])->name('installments');
        Route::get('/customers', [OverdueController::class, 'customers'])->name('customers');
        Route::get('/customer/{customer}', [OverdueController::class, 'customerProfile'])->name('customer-profile');
        Route::get('/branch-aging', [OverdueController::class, 'branchReport'])->name('branch-aging');
    });

    // Automatic Penalty & Late Fee Management Routes
    Route::prefix('penalties')->name('admin.penalties.')->group(function () {
        Route::get('/ledger', [LoanPenaltyController::class, 'ledger'])->name('ledger')->middleware('can:penalty.view');
        Route::post('/waive/{loanAccount}', [LoanPenaltyController::class, 'waive'])->name('waive')->middleware('can:loans.waive_penalty');
    });

    Route::get('/loan', function () { return view('admin.placeholders.module', ['moduleTitle' => 'Loan Management', 'moduleSlug' => 'loan']); });
    Route::get('/savings', function () { return view('admin.placeholders.module', ['moduleTitle' => 'Savings Accounts', 'moduleSlug' => 'savings']); });

    // Module 10: General Ledger & Double-Entry Accounting Routes
    Route::prefix('accounting')->name('admin.accounting.')->middleware('can:accounting.view')->group(function () {
        Route::get('/', [AccountingDashboardController::class, 'index'])->name('dashboard');
        Route::get('/dashboard', [AccountingDashboardController::class, 'index']);
        Route::resource('chart-of-accounts', ChartOfAccountController::class);
        Route::resource('bank-accounts', BankAccountController::class);
        Route::resource('vouchers', VoucherController::class)->except(['edit', 'update', 'destroy']);
        Route::post('vouchers/{voucher}/reverse', [VoucherController::class, 'reverse'])->name('vouchers.reverse');
    });

    // Central Reports Center Routes
    Route::prefix('reports')->name('admin.reports.')->middleware('can:reports.view')->group(function () {
        Route::get('/', [ReportsController::class, 'index'])->name('index');
        Route::get('/{category}/{type}', [ReportsController::class, 'show'])->name('show');
        Route::get('/{category}/{type}/print', [ReportsController::class, 'print'])->name('print');
        Route::get('/{category}/{type}/export', [ReportsController::class, 'export'])->name('export')->middleware('can:reports.export');
    });

    // Website CMS Module
    Route::prefix('cms')->middleware('can:website.manage')->group(function () {
        Route::get('/settings', [WebsiteSettingController::class, 'edit'])->name('admin.cms.settings.edit');
        Route::put('/settings', [WebsiteSettingController::class, 'update'])->name('admin.cms.settings.update');
        Route::get('/homepage', [HomepageSectionController::class, 'index'])->name('admin.cms.homepage.index');
        Route::get('/homepage/create', [HomepageSectionController::class, 'create'])->name('admin.cms.homepage.create');
        Route::post('/homepage', [HomepageSectionController::class, 'store'])->name('admin.cms.homepage.store');
        Route::get('/homepage/{homepage_section}/edit', [HomepageSectionController::class, 'edit'])->name('admin.cms.homepage.edit');
        Route::put('/homepage/{homepage_section}', [HomepageSectionController::class, 'update'])->name('admin.cms.homepage.update');
        Route::patch('/homepage/{homepage_section}/toggle-status', [HomepageSectionController::class, 'toggleStatus'])->name('admin.cms.homepage.toggle-status');
        Route::delete('/homepage/{homepage_section}', [HomepageSectionController::class, 'destroy'])->name('admin.cms.homepage.destroy');
        Route::get('/banners', [BannerController::class, 'index'])->name('admin.cms.banners.index');
        Route::get('/banners/create', [BannerController::class, 'create'])->name('admin.cms.banners.create');
        Route::post('/banners', [BannerController::class, 'store'])->name('admin.cms.banners.store');
        Route::get('/banners/{banner}/edit', [BannerController::class, 'edit'])->name('admin.cms.banners.edit');
        Route::put('/banners/{banner}', [BannerController::class, 'update'])->name('admin.cms.banners.update');
        Route::patch('/banners/{banner}/toggle-status', [BannerController::class, 'toggleStatus'])->name('admin.cms.banners.toggle-status');
        Route::delete('/banners/{banner}', [BannerController::class, 'destroy'])->name('admin.cms.banners.destroy');
        Route::get('/pages', [PageController::class, 'index'])->name('admin.cms.pages.index');
        Route::get('/pages/create', [PageController::class, 'create'])->name('admin.cms.pages.create');
        Route::post('/pages', [PageController::class, 'store'])->name('admin.cms.pages.store');
        Route::get('/pages/{page}/edit', [PageController::class, 'edit'])->name('admin.cms.pages.edit');
        Route::put('/pages/{page}', [PageController::class, 'update'])->name('admin.cms.pages.update');
        Route::patch('/pages/{page}/toggle-status', [PageController::class, 'toggleStatus'])->name('admin.cms.pages.toggle-status');
        Route::delete('/pages/{page}', [PageController::class, 'destroy'])->name('admin.cms.pages.destroy');
        Route::get('/loan-products', [CmsLoanProductController::class, 'index'])->name('admin.cms.loan-products.index');
        Route::get('/loan-products/create', [CmsLoanProductController::class, 'create'])->name('admin.cms.loan-products.create');
        Route::post('/loan-products', [CmsLoanProductController::class, 'store'])->name('admin.cms.loan-products.store');
        Route::get('/loan-products/{loan_product}/edit', [CmsLoanProductController::class, 'edit'])->name('admin.cms.loan-products.edit');
        Route::put('/loan-products/{loan_product}', [CmsLoanProductController::class, 'update'])->name('admin.cms.loan-products.update');
        Route::patch('/loan-products/{loan_product}/toggle-status', [CmsLoanProductController::class, 'toggleStatus'])->name('admin.cms.loan-products.toggle-status');
        Route::delete('/loan-products/{loan_product}', [CmsLoanProductController::class, 'destroy'])->name('admin.cms.loan-products.destroy');
        Route::get('/savings-products', [CmsSavingsProductController::class, 'index'])->name('admin.cms.savings-products.index');
        Route::get('/savings-products/create', [CmsSavingsProductController::class, 'create'])->name('admin.cms.savings-products.create');
        Route::post('/savings-products', [CmsSavingsProductController::class, 'store'])->name('admin.cms.savings-products.store');
        Route::get('/savings-products/{savings_product}/edit', [CmsSavingsProductController::class, 'edit'])->name('admin.cms.savings-products.edit');
        Route::put('/savings-products/{savings_product}', [CmsSavingsProductController::class, 'update'])->name('admin.cms.savings-products.update');
        Route::patch('/savings-products/{savings_product}/toggle-status', [CmsSavingsProductController::class, 'toggleStatus'])->name('admin.cms.savings-products.toggle-status');
        Route::delete('/savings-products/{savings_product}', [CmsSavingsProductController::class, 'destroy'])->name('admin.cms.savings-products.destroy');
        Route::get('/interest-rates', [InterestRateController::class, 'index'])->name('admin.cms.interest-rates.index');
        Route::get('/interest-rates/create', [InterestRateController::class, 'create'])->name('admin.cms.interest-rates.create');
        Route::post('/interest-rates', [InterestRateController::class, 'store'])->name('admin.cms.interest-rates.store');
        Route::get('/interest-rates/{interest_rate}/edit', [InterestRateController::class, 'edit'])->name('admin.cms.interest-rates.edit');
        Route::put('/interest-rates/{interest_rate}', [InterestRateController::class, 'update'])->name('admin.cms.interest-rates.update');
        Route::patch('/interest-rates/{interest_rate}/toggle-status', [InterestRateController::class, 'toggleStatus'])->name('admin.cms.interest-rates.toggle-status');
        Route::delete('/interest-rates/{interest_rate}', [InterestRateController::class, 'destroy'])->name('admin.cms.interest-rates.destroy');
        Route::get('/services', [CmsServiceController::class, 'index'])->name('admin.cms.services.index');
        Route::get('/services/create', [CmsServiceController::class, 'create'])->name('admin.cms.services.create');
        Route::post('/services', [CmsServiceController::class, 'store'])->name('admin.cms.services.store');
        Route::get('/services/{service}/edit', [CmsServiceController::class, 'edit'])->name('admin.cms.services.edit');
        Route::put('/services/{service}', [CmsServiceController::class, 'update'])->name('admin.cms.services.update');
        Route::patch('/services/{service}/toggle-status', [CmsServiceController::class, 'toggleStatus'])->name('admin.cms.services.toggle-status');
        Route::delete('/services/{service}', [CmsServiceController::class, 'destroy'])->name('admin.cms.services.destroy');
        Route::get('/news', [NewsController::class, 'index'])->name('admin.cms.news.index');
        Route::get('/news/create', [NewsController::class, 'create'])->name('admin.cms.news.create');
        Route::post('/news', [NewsController::class, 'store'])->name('admin.cms.news.store');
        Route::get('/news/{news}/edit', [NewsController::class, 'edit'])->name('admin.cms.news.edit');
        Route::put('/news/{news}', [NewsController::class, 'update'])->name('admin.cms.news.update');
        Route::patch('/news/{news}/toggle-status', [NewsController::class, 'toggleStatus'])->name('admin.cms.news.toggle-status');
        Route::delete('/news/{news}', [NewsController::class, 'destroy'])->name('admin.cms.news.destroy');
        Route::get('/gallery', [GalleryController::class, 'index'])->name('admin.cms.gallery.index');
        Route::get('/gallery/create', [GalleryController::class, 'create'])->name('admin.cms.gallery.create');
        Route::post('/gallery', [GalleryController::class, 'store'])->name('admin.cms.gallery.store');
        Route::get('/gallery/{gallery}/edit', [GalleryController::class, 'edit'])->name('admin.cms.gallery.edit');
        Route::put('/gallery/{gallery}', [GalleryController::class, 'update'])->name('admin.cms.gallery.update');
        Route::patch('/gallery/{gallery}/toggle-status', [GalleryController::class, 'toggleStatus'])->name('admin.cms.gallery.toggle-status');
        Route::delete('/gallery/{gallery}', [GalleryController::class, 'destroy'])->name('admin.cms.gallery.destroy');
        Route::get('/downloads', [DownloadController::class, 'index'])->name('admin.cms.downloads.index');
        Route::get('/downloads/create', [DownloadController::class, 'create'])->name('admin.cms.downloads.create');
        Route::post('/downloads', [DownloadController::class, 'store'])->name('admin.cms.downloads.store');
        Route::get('/downloads/{download}/edit', [DownloadController::class, 'edit'])->name('admin.cms.downloads.edit');
        Route::put('/downloads/{download}', [DownloadController::class, 'update'])->name('admin.cms.downloads.update');
        Route::patch('/downloads/{download}/toggle-status', [DownloadController::class, 'toggleStatus'])->name('admin.cms.downloads.toggle-status');
        Route::delete('/downloads/{download}', [DownloadController::class, 'destroy'])->name('admin.cms.downloads.destroy');
        Route::get('/faq', [FaqController::class, 'index'])->name('admin.cms.faq.index');
        Route::get('/faq/create', [FaqController::class, 'create'])->name('admin.cms.faq.create');
        Route::post('/faq', [FaqController::class, 'store'])->name('admin.cms.faq.store');
        Route::get('/faq/{faq}/edit', [FaqController::class, 'edit'])->name('admin.cms.faq.edit');
        Route::put('/faq/{faq}', [FaqController::class, 'update'])->name('admin.cms.faq.update');
        Route::patch('/faq/{faq}/toggle-status', [FaqController::class, 'toggleStatus'])->name('admin.cms.faq.toggle-status');
        Route::delete('/faq/{faq}', [FaqController::class, 'destroy'])->name('admin.cms.faq.destroy');
        Route::get('/footer', [FooterSettingController::class, 'edit'])->name('admin.cms.footer.edit');
        Route::put('/footer', [FooterSettingController::class, 'update'])->name('admin.cms.footer.update');
        Route::get('/seo', [SeoSettingController::class, 'index'])->name('admin.cms.seo.index');
        Route::get('/seo/create', [SeoSettingController::class, 'create'])->name('admin.cms.seo.create');
        Route::post('/seo', [SeoSettingController::class, 'store'])->name('admin.cms.seo.store');
        Route::get('/seo/{seo}/edit', [SeoSettingController::class, 'edit'])->name('admin.cms.seo.edit');
        Route::put('/seo/{seo}', [SeoSettingController::class, 'update'])->name('admin.cms.seo.update');
        Route::patch('/seo/{seo}/toggle-status', [SeoSettingController::class, 'toggleStatus'])->name('admin.cms.seo.toggle-status');
        Route::delete('/seo/{seo}', [SeoSettingController::class, 'destroy'])->name('admin.cms.seo.destroy');
        Route::get('/contact', [ContactInquiryController::class, 'index'])->name('admin.cms.contact.index');
        Route::get('/contact/{contact}', [ContactInquiryController::class, 'show'])->name('admin.cms.contact.show');
        Route::delete('/contact/{contact}', [ContactInquiryController::class, 'destroy'])->name('admin.cms.contact.destroy');
        Route::get('/careers', [CareerController::class, 'index'])->name('admin.cms.careers.index');
        Route::get('/careers/create', [CareerController::class, 'create'])->name('admin.cms.careers.create');
        Route::post('/careers', [CareerController::class, 'store'])->name('admin.cms.careers.store');
        Route::get('/careers/{career}/edit', [CareerController::class, 'edit'])->name('admin.cms.careers.edit');
        Route::put('/careers/{career}', [CareerController::class, 'update'])->name('admin.cms.careers.update');
        Route::patch('/careers/{career}/toggle-status', [CareerController::class, 'toggleStatus'])->name('admin.cms.careers.toggle-status');
        Route::delete('/careers/{career}', [CareerController::class, 'destroy'])->name('admin.cms.careers.destroy');
        Route::get('/team', [TeamMemberController::class, 'index'])->name('admin.cms.team.index');
        Route::get('/team/create', [TeamMemberController::class, 'create'])->name('admin.cms.team.create');
        Route::post('/team', [TeamMemberController::class, 'store'])->name('admin.cms.team.store');
        Route::get('/team/{team_member}/edit', [TeamMemberController::class, 'edit'])->name('admin.cms.team.edit');
        Route::put('/team/{team_member}', [TeamMemberController::class, 'update'])->name('admin.cms.team.update');
        Route::patch('/team/{team_member}/toggle-status', [TeamMemberController::class, 'toggleStatus'])->name('admin.cms.team.toggle-status');
        Route::delete('/team/{team_member}', [TeamMemberController::class, 'destroy'])->name('admin.cms.team.destroy');
    });
});

