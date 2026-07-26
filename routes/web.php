<?php

use App\Http\Controllers\Auth\LoginController;
use App\Http\Middleware\EnsureAdminAuthenticated;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| TG Microfinance ERP - Public Website Routes
|--------------------------------------------------------------------------
*/

Route::get('/', function () {
    return view('public.home');
})->name('public.home');

// About Routes
Route::prefix('about')->group(function () {
    Route::get('/', function () { return view('public.about.index'); })->name('public.about.index');
    Route::get('/mission', function () { return view('public.about.mission'); })->name('public.about.mission');
    Route::get('/vision', function () { return view('public.about.vision'); })->name('public.about.vision');
    Route::get('/board-of-directors', function () { return view('public.about.board-of-directors'); })->name('public.about.board-of-directors');
    Route::get('/management-team', function () { return view('public.about.management-team'); })->name('public.about.management-team');
});

// Products Routes
Route::prefix('products')->group(function () {
    Route::get('/loan', function () { return view('public.products.loan'); })->name('public.products.loan');
    Route::get('/savings', function () { return view('public.products.savings'); })->name('public.products.savings');
    Route::get('/interest-rates', function () { return view('public.products.interest-rates'); })->name('public.products.interest-rates');
});

// Services Routes
Route::prefix('services')->group(function () {
    Route::get('/digital-banking', function () { return view('public.services.digital-banking'); })->name('public.services.digital-banking');
    Route::get('/collection-services', function () { return view('public.services.collection-services'); })->name('public.services.collection-services');
    Route::get('/financial-advisory', function () { return view('public.services.financial-advisory'); })->name('public.services.financial-advisory');
});

// Resources Routes
Route::prefix('resources')->group(function () {
    Route::get('/gallery', function () { return view('public.resources.gallery'); })->name('public.resources.gallery');
    Route::get('/downloads', function () { return view('public.resources.downloads'); })->name('public.resources.downloads');
    Route::get('/news', function () { return view('public.resources.news'); })->name('public.resources.news');
    Route::get('/faq', function () { return view('public.resources.faq'); })->name('public.resources.faq');
    Route::get('/career', function () { return view('public.resources.career'); })->name('public.resources.career');
});

// General Public Routes
Route::get('/branches', function () { return view('public.branches'); })->name('public.branches');
Route::get('/contact', function () { return view('public.contact'); })->name('public.contact');
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
| TG Microfinance ERP - Admin Panel Foundation Routes (Protected)
|--------------------------------------------------------------------------
*/

Route::middleware([EnsureAdminAuthenticated::class])->prefix('admin')->group(function () {
    Route::get('/', function () { return view('admin.dashboard'); })->name('admin.dashboard');
    Route::get('/dashboard', function () { return view('admin.dashboard'); });

    // ERP Core Module Placeholders
    Route::get('/company', function () { return view('admin.placeholders.module', ['moduleTitle' => 'Company Management', 'moduleSlug' => 'company']); });
    Route::get('/branch', function () { return view('admin.placeholders.module', ['moduleTitle' => 'Branch Management', 'moduleSlug' => 'branch']); });
    Route::get('/employee', function () { return view('admin.placeholders.module', ['moduleTitle' => 'Employee Management', 'moduleSlug' => 'employee']); });
    Route::get('/customer', function () { return view('admin.placeholders.module', ['moduleTitle' => 'Customer Management', 'moduleSlug' => 'customer']); });
    Route::get('/loan', function () { return view('admin.placeholders.module', ['moduleTitle' => 'Loan Management', 'moduleSlug' => 'loan']); });
    Route::get('/savings', function () { return view('admin.placeholders.module', ['moduleTitle' => 'Savings Accounts', 'moduleSlug' => 'savings']); });
    Route::get('/collection', function () { return view('admin.placeholders.module', ['moduleTitle' => 'Field Collections', 'moduleSlug' => 'collection']); });
    Route::get('/accounting', function () { return view('admin.placeholders.module', ['moduleTitle' => 'General Ledger Accounting', 'moduleSlug' => 'accounting']); });
    Route::get('/reports', function () { return view('admin.placeholders.module', ['moduleTitle' => 'Financial Reports', 'moduleSlug' => 'reports']); });

    // Website CMS Module Placeholders
    Route::prefix('cms')->group(function () {
        Route::get('/homepage', function () { return view('admin.placeholders.module', ['moduleTitle' => 'CMS Homepage Manager', 'moduleSlug' => 'cms/homepage']); });
        Route::get('/pages', function () { return view('admin.placeholders.module', ['moduleTitle' => 'CMS Pages Manager', 'moduleSlug' => 'cms/pages']); });
        Route::get('/banners', function () { return view('admin.placeholders.module', ['moduleTitle' => 'CMS Banner Manager', 'moduleSlug' => 'cms/banners']); });
        Route::get('/loan-products', function () { return view('admin.placeholders.module', ['moduleTitle' => 'CMS Loan Products', 'moduleSlug' => 'cms/loan-products']); });
        Route::get('/savings-products', function () { return view('admin.placeholders.module', ['moduleTitle' => 'CMS Savings Products', 'moduleSlug' => 'cms/savings-products']); });
        Route::get('/news', function () { return view('admin.placeholders.module', ['moduleTitle' => 'CMS News Manager', 'moduleSlug' => 'cms/news']); });
        Route::get('/gallery', function () { return view('admin.placeholders.module', ['moduleTitle' => 'CMS Gallery Manager', 'moduleSlug' => 'cms/gallery']); });
        Route::get('/downloads', function () { return view('admin.placeholders.module', ['moduleTitle' => 'CMS Downloads Manager', 'moduleSlug' => 'cms/downloads']); });
        Route::get('/faq', function () { return view('admin.placeholders.module', ['moduleTitle' => 'CMS FAQ Manager', 'moduleSlug' => 'cms/faq']); });
        Route::get('/contact', function () { return view('admin.placeholders.module', ['moduleTitle' => 'CMS Contact Manager', 'moduleSlug' => 'cms/contact']); });
        Route::get('/footer', function () { return view('admin.placeholders.module', ['moduleTitle' => 'CMS Footer Manager', 'moduleSlug' => 'cms/footer']); });
        Route::get('/seo', function () { return view('admin.placeholders.module', ['moduleTitle' => 'CMS SEO Settings', 'moduleSlug' => 'cms/seo']); });
    });

    // System Module Placeholders
    Route::prefix('system')->group(function () {
        Route::get('/users', function () { return view('admin.placeholders.module', ['moduleTitle' => 'System Users', 'moduleSlug' => 'system/users']); });
        Route::get('/roles', function () { return view('admin.placeholders.module', ['moduleTitle' => 'Roles & RBAC', 'moduleSlug' => 'system/roles']); });
        Route::get('/permissions', function () { return view('admin.placeholders.module', ['moduleTitle' => 'Permissions Matrix', 'moduleSlug' => 'system/permissions']); });
        Route::get('/settings', function () { return view('admin.placeholders.module', ['moduleTitle' => 'System Settings', 'moduleSlug' => 'system/settings']); });
        Route::get('/media', function () { return view('admin.placeholders.module', ['moduleTitle' => 'Media Library', 'moduleSlug' => 'system/media']); });
        Route::get('/notifications', function () { return view('admin.placeholders.module', ['moduleTitle' => 'System Notifications', 'moduleSlug' => 'system/notifications']); });
        Route::get('/audit-logs', function () { return view('admin.placeholders.module', ['moduleTitle' => 'Audit Logs', 'moduleSlug' => 'system/audit-logs']); });
        Route::get('/backup', function () { return view('admin.placeholders.module', ['moduleTitle' => 'Database Backup', 'moduleSlug' => 'system/backup']); });
    });
});
