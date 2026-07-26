# Enterprise Folder Architecture Specification - TG Microfinance ERP

## Overview
This document defines the complete directory tree structure and namespace organization for **TG Microfinance ERP**. The architecture enforces strict domain isolation under `app/Domain/` and presentation delivery under `app/Http/`.

---

## Directory Hierarchy Tree

```text
c:\Projects\MicrofinanceERP\
├── app/
│   ├── Domain/                              # Clean Architecture Core Business Logic
│   │   ├── Company/
│   │   │   ├── DTOs/
│   │   │   ├── Enums/
│   │   │   ├── Models/
│   │   │   ├── Repositories/
│   │   │   └── Services/
│   │   ├── Branch/
│   │   │   ├── DTOs/
│   │   │   ├── Enums/
│   │   │   ├── Models/
│   │   │   ├── Repositories/
│   │   │   └── Services/
│   │   ├── Employee/
│   │   ├── Customer/
│   │   ├── Loan/
│   │   │   ├── DTOs/                        # LoanApplicationDTO, DisbursementDTO
│   │   │   ├── Enums/                       # LoanStatusEnum, InterestTypeEnum
│   │   │   ├── Models/                      # Loan, LoanProduct, LoanSchedule, LoanRepayment
│   │   │   ├── Repositories/                # LoanRepositoryInterface, EloquentLoanRepository
│   │   │   └── Services/                    # InterestCalculatorService, ScheduleGeneratorService
│   │   ├── Savings/
│   │   ├── Collection/
│   │   ├── Accounting/
│   │   │   ├── Models/                      # ChartOfAccount, JournalEntry, JournalItem
│   │   │   └── Services/                    # GeneralLedgerService, TrialBalanceService
│   │   ├── Reports/
│   │   ├── Settings/
│   │   └── Website/
│   │
│   ├── Http/                                # Interface Delivery Layer
│   │   ├── Controllers/
│   │   │   ├── Public/                      # Public Corporate Website Controllers
│   │   │   │   ├── HomeController.php
│   │   │   │   ├── AboutController.php
│   │   │   │   ├── ServiceController.php
│   │   │   │   ├── ProductController.php
│   │   │   │   ├── BranchController.php
│   │   │   │   ├── GalleryController.php
│   │   │   │   ├── DownloadController.php
│   │   │   │   ├── FaqController.php
│   │   │   │   ├── CareerController.php
│   │   │   │   ├── ContactController.php
│   │   │   │   └── LoanApplicationController.php
│   │   │   ├── Admin/                       # ERP Admin Panel Controllers
│   │   │   │   ├── DashboardController.php
│   │   │   │   ├── CompanyController.php
│   │   │   │   ├── BranchController.php
│   │   │   │   ├── EmployeeController.php
│   │   │   │   ├── CustomerController.php
│   │   │   │   ├── LoanController.php
│   │   │   │   ├── SavingsController.php
│   │   │   │   ├── CollectionController.php
│   │   │   │   ├── AccountingController.php
│   │   │   │   ├── ReportController.php
│   │   │   │   └── SettingController.php
│   │   │   └── Auth/
│   │   │       └── LoginController.php
│   │   │
│   │   ├── Middleware/
│   │   │   ├── RoleMiddleware.php           # RBAC role checking
│   │   │   ├── BranchScopeMiddleware.php    # Multi-branch query isolation
│   │   │   └── AuditTrailMiddleware.php     # Compliance audit logger
│   │   │
│   │   ├── Requests/                        # Form Requests
│   │   │   ├── Public/
│   │   │   └── Admin/
│   │   │
│   │   └── View/Composers/                  # View Composers
│   │
│   ├── Providers/
│   │   ├── AppServiceProvider.php
│   │   └── RepositoryServiceProvider.php    # Binds domain interfaces to Eloquent repos
│   │
│   └── Support/                             # Shared Enums, Value Objects, Format Helpers
│       ├── Enums/
│       │   └── UserRole.php                 # 8 Enterprise roles
│       └── Helpers/
│           └── AmountFormatter.php
│
├── database/
│   ├── factories/
│   ├── seeders/
│   │   ├── RoleAndPermissionSeeder.php
│   │   ├── CompanyBranchSeeder.php
│   │   ├── UserSeeder.php
│   │   └── ChartOfAccountsSeeder.php
│   └── migrations/                          # Standard migrations
│
├── resources/
│   ├── css/
│   │   ├── public.css                       # Public website styles
│   │   └── admin.css                        # ERP admin dashboard styles
│   ├── js/
│   │   ├── public.js
│   │   └── admin.js
│   └── views/
│       ├── components/                      # Reusable Blade UI components
│       ├── layouts/
│       │   ├── public.blade.php             # Corporate website layout
│       │   ├── admin.blade.php              # ERP Admin panel layout
│       │   └── auth.blade.php               # Login layout
│       ├── public/                          # Public views
│       └── admin/                           # Admin ERP views
│
└── routes/
    ├── web.php                              # Master route aggregator
    ├── public.php                           # Corporate Website routes
    ├── auth.php                             # Login & logout routes
    └── admin.php                            # Protected Admin ERP routes
```

---

## PSR-12 Conventions & Rules
1. **Namespaces**: Match PSR-4 declaration (`App\Domain\{Module}\...`, `App\Http\Controllers\{Public|Admin|Auth}`).
2. **Class Files**: One class/interface/enum per file with `declare(strict_types=1);`.
3. **Controller Responsibility**: Controllers must never write raw SQL queries or execute financial interest calculations directly. They delegate to Domain Services or Repositories.
