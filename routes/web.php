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
use App\Http\Controllers\Admin\Cms\WhyChooseUsController;
use App\Http\Controllers\Admin\BranchController;
use App\Http\Controllers\Admin\CompanyController;
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
    Route::get('/', function () { return view('admin.dashboard'); })->name('admin.dashboard');
    Route::get('/dashboard', function () { return view('admin.dashboard'); });

    // Profile Management Routes
    Route::get('/profile', [ProfileController::class, 'show'])->name('admin.profile.show');
    Route::put('/profile', [ProfileController::class, 'updateProfile'])->name('admin.profile.update');
    Route::put('/profile/password', [ProfileController::class, 'changePassword'])->name('admin.profile.password');

    // System Modules - Real Functional RBAC Routes
    Route::prefix('system')->group(function () {
        // User Management CRUD
        Route::get('/users', [UserController::class, 'index'])->name('admin.system.users.index');
        Route::get('/users/create', [UserController::class, 'create'])->name('admin.system.users.create');
        Route::post('/users', [UserController::class, 'store'])->name('admin.system.users.store');
        Route::get('/users/{user}/edit', [UserController::class, 'edit'])->name('admin.system.users.edit');
        Route::put('/users/{user}', [UserController::class, 'update'])->name('admin.system.users.update');
        Route::patch('/users/{user}/toggle-status', [UserController::class, 'toggleStatus'])->name('admin.system.users.toggle-status');
        Route::delete('/users/{user}', [UserController::class, 'destroy'])->name('admin.system.users.destroy');

        // Role Management CRUD
        Route::get('/roles', [RoleController::class, 'index'])->name('admin.system.roles.index');
        Route::get('/roles/create', [RoleController::class, 'create'])->name('admin.system.roles.create');
        Route::post('/roles', [RoleController::class, 'store'])->name('admin.system.roles.store');
        Route::get('/roles/{role}/edit', [RoleController::class, 'edit'])->name('admin.system.roles.edit');
        Route::put('/roles/{role}', [RoleController::class, 'update'])->name('admin.system.roles.update');
        Route::delete('/roles/{role}', [RoleController::class, 'destroy'])->name('admin.system.roles.destroy');

        // Permissions Matrix
        Route::get('/permissions', [PermissionController::class, 'index'])->name('admin.system.permissions.index');
        Route::post('/permissions', [PermissionController::class, 'store'])->name('admin.system.permissions.store');
        Route::delete('/permissions/{permission}', [PermissionController::class, 'destroy'])->name('admin.system.permissions.destroy');

        // System Placeholder Subpages
        Route::get('/settings', function () { return view('admin.placeholders.module', ['moduleTitle' => 'System Settings', 'moduleSlug' => 'system/settings']); });
        Route::get('/media', function () { return view('admin.placeholders.module', ['moduleTitle' => 'Media Library', 'moduleSlug' => 'system/media']); });
        Route::get('/notifications', function () { return view('admin.placeholders.module', ['moduleTitle' => 'System Notifications', 'moduleSlug' => 'system/notifications']); });
        Route::get('/audit-logs', function () { return view('admin.placeholders.module', ['moduleTitle' => 'Audit Logs', 'moduleSlug' => 'system/audit-logs']); });
        Route::get('/backup', function () { return view('admin.placeholders.module', ['moduleTitle' => 'Database Backup', 'moduleSlug' => 'system/backup']); });
    });

    // ERP Core Modules - Real Functional Company & Branch Routes
    Route::get('/company', [CompanyController::class, 'index'])->name('admin.company.index');
    Route::get('/company/create', [CompanyController::class, 'create'])->name('admin.company.create');
    Route::post('/company', [CompanyController::class, 'store'])->name('admin.company.store');
    Route::get('/company/{company}', [CompanyController::class, 'show'])->name('admin.company.show');
    Route::get('/company/{company}/edit', [CompanyController::class, 'edit'])->name('admin.company.edit');
    Route::put('/company/{company}', [CompanyController::class, 'update'])->name('admin.company.update');
    Route::patch('/company/{company}/toggle-status', [CompanyController::class, 'toggleStatus'])->name('admin.company.toggle-status');
    Route::delete('/company/{company}', [CompanyController::class, 'destroy'])->name('admin.company.destroy');
    Route::post('/company/{id}/restore', [CompanyController::class, 'restore'])->name('admin.company.restore');

    Route::get('/branch', [BranchController::class, 'index'])->name('admin.branch.index');
    Route::get('/branch/create', [BranchController::class, 'create'])->name('admin.branch.create');
    Route::post('/branch', [BranchController::class, 'store'])->name('admin.branch.store');
    Route::get('/branch/{branch}', [BranchController::class, 'show'])->name('admin.branch.show');
    Route::get('/branch/{branch}/edit', [BranchController::class, 'edit'])->name('admin.branch.edit');
    Route::put('/branch/{branch}', [BranchController::class, 'update'])->name('admin.branch.update');
    Route::patch('/branch/{branch}/toggle-status', [BranchController::class, 'toggleStatus'])->name('admin.branch.toggle-status');
    Route::delete('/branch/{branch}', [BranchController::class, 'destroy'])->name('admin.branch.destroy');
    Route::post('/branch/{id}/restore', [BranchController::class, 'restore'])->name('admin.branch.restore');
    Route::get('/employee', function () { return view('admin.placeholders.module', ['moduleTitle' => 'Employee Management', 'moduleSlug' => 'employee']); });
    Route::get('/customer', function () { return view('admin.placeholders.module', ['moduleTitle' => 'Member Management', 'moduleSlug' => 'customer']); });
    Route::get('/loan', function () { return view('admin.placeholders.module', ['moduleTitle' => 'Loan Management', 'moduleSlug' => 'loan']); });
    Route::get('/savings', function () { return view('admin.placeholders.module', ['moduleTitle' => 'Savings Accounts', 'moduleSlug' => 'savings']); });
    Route::get('/collection', function () { return view('admin.placeholders.module', ['moduleTitle' => 'Field Collections', 'moduleSlug' => 'collection']); });
    Route::get('/inventory', function () { return view('admin.placeholders.module', ['moduleTitle' => 'Inventory Management', 'moduleSlug' => 'inventory']); });
    Route::get('/billing', function () { return view('admin.placeholders.module', ['moduleTitle' => 'Billing & Counter Invoices', 'moduleSlug' => 'billing']); });
    Route::get('/accounting', function () { return view('admin.placeholders.module', ['moduleTitle' => 'General Ledger Accounting', 'moduleSlug' => 'accounting']); });
    Route::get('/reports', function () { return view('admin.placeholders.module', ['moduleTitle' => 'Financial Reports', 'moduleSlug' => 'reports']); });

    // Website CMS Module
    Route::prefix('cms')->group(function () {
        // Website Settings
        Route::get('/settings', [WebsiteSettingController::class, 'edit'])->name('admin.cms.settings.edit');
        Route::put('/settings', [WebsiteSettingController::class, 'update'])->name('admin.cms.settings.update');

        // Homepage Sections CRUD & Status Toggle
        Route::get('/homepage', [HomepageSectionController::class, 'index'])->name('admin.cms.homepage.index');
        Route::get('/homepage/create', [HomepageSectionController::class, 'create'])->name('admin.cms.homepage.create');
        Route::post('/homepage', [HomepageSectionController::class, 'store'])->name('admin.cms.homepage.store');
        Route::get('/homepage/{homepage_section}/edit', [HomepageSectionController::class, 'edit'])->name('admin.cms.homepage.edit');
        Route::put('/homepage/{homepage_section}', [HomepageSectionController::class, 'update'])->name('admin.cms.homepage.update');
        Route::patch('/homepage/{homepage_section}/toggle-status', [HomepageSectionController::class, 'toggleStatus'])->name('admin.cms.homepage.toggle-status');
        Route::delete('/homepage/{homepage_section}', [HomepageSectionController::class, 'destroy'])->name('admin.cms.homepage.destroy');

        // Banners CRUD & Status Toggle
        Route::get('/banners', [BannerController::class, 'index'])->name('admin.cms.banners.index');
        Route::get('/banners/create', [BannerController::class, 'create'])->name('admin.cms.banners.create');
        Route::post('/banners', [BannerController::class, 'store'])->name('admin.cms.banners.store');
        Route::get('/banners/{banner}/edit', [BannerController::class, 'edit'])->name('admin.cms.banners.edit');
        Route::put('/banners/{banner}', [BannerController::class, 'update'])->name('admin.cms.banners.update');
        Route::patch('/banners/{banner}/toggle-status', [BannerController::class, 'toggleStatus'])->name('admin.cms.banners.toggle-status');
        Route::delete('/banners/{banner}', [BannerController::class, 'destroy'])->name('admin.cms.banners.destroy');

        // Pages CRUD & Status Toggle
        Route::get('/pages', [PageController::class, 'index'])->name('admin.cms.pages.index');
        Route::get('/pages/create', [PageController::class, 'create'])->name('admin.cms.pages.create');
        Route::post('/pages', [PageController::class, 'store'])->name('admin.cms.pages.store');
        Route::get('/pages/{page}/edit', [PageController::class, 'edit'])->name('admin.cms.pages.edit');
        Route::put('/pages/{page}', [PageController::class, 'update'])->name('admin.cms.pages.update');
        Route::patch('/pages/{page}/toggle-status', [PageController::class, 'toggleStatus'])->name('admin.cms.pages.toggle-status');
        Route::delete('/pages/{page}', [PageController::class, 'destroy'])->name('admin.cms.pages.destroy');

        // Loan Products CRUD & Status Toggle
        Route::get('/loan-products', [CmsLoanProductController::class, 'index'])->name('admin.cms.loan-products.index');
        Route::get('/loan-products/create', [CmsLoanProductController::class, 'create'])->name('admin.cms.loan-products.create');
        Route::post('/loan-products', [CmsLoanProductController::class, 'store'])->name('admin.cms.loan-products.store');
        Route::get('/loan-products/{loan_product}/edit', [CmsLoanProductController::class, 'edit'])->name('admin.cms.loan-products.edit');
        Route::put('/loan-products/{loan_product}', [CmsLoanProductController::class, 'update'])->name('admin.cms.loan-products.update');
        Route::patch('/loan-products/{loan_product}/toggle-status', [CmsLoanProductController::class, 'toggleStatus'])->name('admin.cms.loan-products.toggle-status');
        Route::delete('/loan-products/{loan_product}', [CmsLoanProductController::class, 'destroy'])->name('admin.cms.loan-products.destroy');

        // Savings Products CRUD & Status Toggle
        Route::get('/savings-products', [CmsSavingsProductController::class, 'index'])->name('admin.cms.savings-products.index');
        Route::get('/savings-products/create', [CmsSavingsProductController::class, 'create'])->name('admin.cms.savings-products.create');
        Route::post('/savings-products', [CmsSavingsProductController::class, 'store'])->name('admin.cms.savings-products.store');
        Route::get('/savings-products/{savings_product}/edit', [CmsSavingsProductController::class, 'edit'])->name('admin.cms.savings-products.edit');
        Route::put('/savings-products/{savings_product}', [CmsSavingsProductController::class, 'update'])->name('admin.cms.savings-products.update');
        Route::patch('/savings-products/{savings_product}/toggle-status', [CmsSavingsProductController::class, 'toggleStatus'])->name('admin.cms.savings-products.toggle-status');
        Route::delete('/savings-products/{savings_product}', [CmsSavingsProductController::class, 'destroy'])->name('admin.cms.savings-products.destroy');

        // News CRUD & Status Toggle
        Route::get('/news', [NewsController::class, 'index'])->name('admin.cms.news.index');
        Route::get('/news/create', [NewsController::class, 'create'])->name('admin.cms.news.create');
        Route::post('/news', [NewsController::class, 'store'])->name('admin.cms.news.store');
        Route::get('/news/{news}/edit', [NewsController::class, 'edit'])->name('admin.cms.news.edit');
        Route::put('/news/{news}', [NewsController::class, 'update'])->name('admin.cms.news.update');
        Route::patch('/news/{news}/toggle-status', [NewsController::class, 'toggleStatus'])->name('admin.cms.news.toggle-status');
        Route::delete('/news/{news}', [NewsController::class, 'destroy'])->name('admin.cms.news.destroy');

        // Gallery CRUD & Status Toggle
        Route::get('/gallery', [GalleryController::class, 'index'])->name('admin.cms.gallery.index');
        Route::get('/gallery/create', [GalleryController::class, 'create'])->name('admin.cms.gallery.create');
        Route::post('/gallery', [GalleryController::class, 'store'])->name('admin.cms.gallery.store');
        Route::get('/gallery/{gallery}/edit', [GalleryController::class, 'edit'])->name('admin.cms.gallery.edit');
        Route::put('/gallery/{gallery}', [GalleryController::class, 'update'])->name('admin.cms.gallery.update');
        Route::patch('/gallery/{gallery}/toggle-status', [GalleryController::class, 'toggleStatus'])->name('admin.cms.gallery.toggle-status');
        Route::delete('/gallery/{gallery}', [GalleryController::class, 'destroy'])->name('admin.cms.gallery.destroy');

        // Downloads CRUD & Status Toggle
        Route::get('/downloads', [DownloadController::class, 'index'])->name('admin.cms.downloads.index');
        Route::get('/downloads/create', [DownloadController::class, 'create'])->name('admin.cms.downloads.create');
        Route::post('/downloads', [DownloadController::class, 'store'])->name('admin.cms.downloads.store');
        Route::get('/downloads/{download}/edit', [DownloadController::class, 'edit'])->name('admin.cms.downloads.edit');
        Route::put('/downloads/{download}', [DownloadController::class, 'update'])->name('admin.cms.downloads.update');
        Route::patch('/downloads/{download}/toggle-status', [DownloadController::class, 'toggleStatus'])->name('admin.cms.downloads.toggle-status');
        Route::delete('/downloads/{download}', [DownloadController::class, 'destroy'])->name('admin.cms.downloads.destroy');

        // FAQ CRUD & Status Toggle
        Route::get('/faq', [FaqController::class, 'index'])->name('admin.cms.faq.index');
        Route::get('/faq/create', [FaqController::class, 'create'])->name('admin.cms.faq.create');
        Route::post('/faq', [FaqController::class, 'store'])->name('admin.cms.faq.store');
        Route::get('/faq/{faq}/edit', [FaqController::class, 'edit'])->name('admin.cms.faq.edit');
        Route::put('/faq/{faq}', [FaqController::class, 'update'])->name('admin.cms.faq.update');
        Route::patch('/faq/{faq}/toggle-status', [FaqController::class, 'toggleStatus'])->name('admin.cms.faq.toggle-status');
        Route::delete('/faq/{faq}', [FaqController::class, 'destroy'])->name('admin.cms.faq.destroy');

        // Footer CMS Edit & Update
        Route::get('/footer', [FooterSettingController::class, 'edit'])->name('admin.cms.footer.edit');
        Route::put('/footer', [FooterSettingController::class, 'update'])->name('admin.cms.footer.update');

        // SEO CMS CRUD & Status Toggle
        Route::get('/seo', [SeoSettingController::class, 'index'])->name('admin.cms.seo.index');
        Route::get('/seo/create', [SeoSettingController::class, 'create'])->name('admin.cms.seo.create');
        Route::post('/seo', [SeoSettingController::class, 'store'])->name('admin.cms.seo.store');
        Route::get('/seo/{seo}/edit', [SeoSettingController::class, 'edit'])->name('admin.cms.seo.edit');
        Route::put('/seo/{seo}', [SeoSettingController::class, 'update'])->name('admin.cms.seo.update');
        Route::patch('/seo/{seo}/toggle-status', [SeoSettingController::class, 'toggleStatus'])->name('admin.cms.seo.toggle-status');
        Route::delete('/seo/{seo}', [SeoSettingController::class, 'destroy'])->name('admin.cms.seo.destroy');

        // Contact Inquiries CMS Inbox, Show, Toggle Read & Delete
        Route::get('/contact', [ContactInquiryController::class, 'index'])->name('admin.cms.contact.index');
        Route::get('/contact/{contact}', [ContactInquiryController::class, 'show'])->name('admin.cms.contact.show');
        Route::patch('/contact/{contact}/toggle-status', [ContactInquiryController::class, 'toggleStatus'])->name('admin.cms.contact.toggle-status');
        Route::delete('/contact/{contact}', [ContactInquiryController::class, 'destroy'])->name('admin.cms.contact.destroy');

        // Why Choose Us CRUD & Status Toggle
        Route::get('/why-choose-us', [WhyChooseUsController::class, 'index'])->name('admin.cms.why-choose-us.index');
        Route::get('/why-choose-us/create', [WhyChooseUsController::class, 'create'])->name('admin.cms.why-choose-us.create');
        Route::post('/why-choose-us', [WhyChooseUsController::class, 'store'])->name('admin.cms.why-choose-us.store');
        Route::get('/why-choose-us/{why_choose_u}/edit', [WhyChooseUsController::class, 'edit'])->name('admin.cms.why-choose-us.edit');
        Route::put('/why-choose-us/{why_choose_u}', [WhyChooseUsController::class, 'update'])->name('admin.cms.why-choose-us.update');
        Route::patch('/why-choose-us/{why_choose_u}/toggle-status', [WhyChooseUsController::class, 'toggleStatus'])->name('admin.cms.why-choose-us.toggle-status');
        Route::delete('/why-choose-us/{why_choose_u}', [WhyChooseUsController::class, 'destroy'])->name('admin.cms.why-choose-us.destroy');

        // Team Members CRUD & Status Toggle
        Route::get('/team', [TeamMemberController::class, 'index'])->name('admin.cms.team.index');
        Route::get('/team/create', [TeamMemberController::class, 'create'])->name('admin.cms.team.create');
        Route::post('/team', [TeamMemberController::class, 'store'])->name('admin.cms.team.store');
        Route::get('/team/{team}/edit', [TeamMemberController::class, 'edit'])->name('admin.cms.team.edit');
        Route::put('/team/{team}', [TeamMemberController::class, 'update'])->name('admin.cms.team.update');
        Route::patch('/team/{team}/toggle-status', [TeamMemberController::class, 'toggleStatus'])->name('admin.cms.team.toggle-status');
        Route::delete('/team/{team}', [TeamMemberController::class, 'destroy'])->name('admin.cms.team.destroy');

        // Interest Rates CRUD & Status Toggle
        Route::get('/interest-rates', [InterestRateController::class, 'index'])->name('admin.cms.interest-rates.index');
        Route::get('/interest-rates/create', [InterestRateController::class, 'create'])->name('admin.cms.interest-rates.create');
        Route::post('/interest-rates', [InterestRateController::class, 'store'])->name('admin.cms.interest-rates.store');
        Route::get('/interest-rates/{interest_rate}/edit', [InterestRateController::class, 'edit'])->name('admin.cms.interest-rates.edit');
        Route::put('/interest-rates/{interest_rate}', [InterestRateController::class, 'update'])->name('admin.cms.interest-rates.update');
        Route::patch('/interest-rates/{interest_rate}/toggle-status', [InterestRateController::class, 'toggleStatus'])->name('admin.cms.interest-rates.toggle-status');
        Route::delete('/interest-rates/{interest_rate}', [InterestRateController::class, 'destroy'])->name('admin.cms.interest-rates.destroy');

        // Services CRUD & Status Toggle
        Route::get('/services', [CmsServiceController::class, 'index'])->name('admin.cms.services.index');
        Route::get('/services/create', [CmsServiceController::class, 'create'])->name('admin.cms.services.create');
        Route::post('/services', [CmsServiceController::class, 'store'])->name('admin.cms.services.store');
        Route::get('/services/{service}/edit', [CmsServiceController::class, 'edit'])->name('admin.cms.services.edit');
        Route::put('/services/{service}', [CmsServiceController::class, 'update'])->name('admin.cms.services.update');
        Route::patch('/services/{service}/toggle-status', [CmsServiceController::class, 'toggleStatus'])->name('admin.cms.services.toggle-status');
        Route::delete('/services/{service}', [CmsServiceController::class, 'destroy'])->name('admin.cms.services.destroy');

        // Careers CRUD & Status Toggle
        Route::get('/careers', [CareerController::class, 'index'])->name('admin.cms.careers.index');
        Route::get('/careers/create', [CareerController::class, 'create'])->name('admin.cms.careers.create');
        Route::post('/careers', [CareerController::class, 'store'])->name('admin.cms.careers.store');
        Route::get('/careers/{career}/edit', [CareerController::class, 'edit'])->name('admin.cms.careers.edit');
        Route::put('/careers/{career}', [CareerController::class, 'update'])->name('admin.cms.careers.update');
        Route::patch('/careers/{career}/toggle-status', [CareerController::class, 'toggleStatus'])->name('admin.cms.careers.toggle-status');
        Route::delete('/careers/{career}', [CareerController::class, 'destroy'])->name('admin.cms.careers.destroy');
    });
});
