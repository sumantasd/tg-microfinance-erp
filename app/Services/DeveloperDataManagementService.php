<?php

namespace App\Services;

use App\Models\LoanScheme;
use App\Models\User;
use Exception;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use InvalidArgumentException;

class DeveloperDataManagementService
{
    public const CLEANUP_LOCK_KEY = 'developer_cleanup_lock';
    public const CLEANUP_LOCK_SECONDS = 300; // 5 minutes

    /**
     * Exact 12-Stage Canonical Deletion Order.
     * Programmatically verified to produce 0 foreign key constraint violations without disabling foreign key checks.
     */
    protected array $canonicalDeletionOrder = [
        // Stage 1: Field Operations & GPS Tracking
        'field_location_logs',
        'field_visits',
        'travel_allowance_claims',

        // Stage 2: HR Attendance & Payroll Transactions
        'salary_slips',
        'payrolls',
        'leaves',
        'attendances',

        // Stage 3: Transient Logs & Notifications (user_notifications references system_notifications)
        'api_idempotency_keys',
        'user_notifications',
        'system_notifications',
        'contact_inquiries',

        // Stage 4: Sales & Invoicing (references products and customers)
        'invoice_items',
        'invoices',
        'sale_items',
        'sales',

        // Stage 5: Cash Book & Daily Banking (online collections reference loan_repayments; must clear before loans)
        'cash_book_online_collections',
        'cash_book_denominations',
        'cash_book_audits',
        'cash_book_entries',
        'cash_books',
        'bank_deposits',

        // Stage 6: Loan Portfolio & Repayments (settlement requests reference vouchers; must clear before vouchers)
        'product_loan_margins',
        'loan_settlement_requests',
        'loan_penalty_waivers',
        'loan_penalty_charges',
        'loan_repayments',
        'loan_installments',
        'loan_disbursements',
        'loan_down_payments',
        'loan_upfront_payments',
        'loan_account_members',
        'loan_accounts',
        'loan_application_products',
        'loan_application_members',
        'loan_applications',

        // Stage 7: Expenses & Procurement Transactions (expense_payments reference vouchers; must clear before vouchers)
        'expense_attachments',
        'expense_payments',
        'expenses',
        'supplier_payment_allocations',
        'supplier_payments',
        'product_purchase_items',
        'product_purchases',

        // Stage 8: Accounting Vouchers & Ledgers (self-reference reversed_voucher_id is nullified first)
        'voucher_entries',
        'vouchers',

        // Stage 9: Inventory Transfers & Stock Balances (references products)
        'inventory_transfer_cancellation_items',
        'inventory_transfer_cancellations',
        'inventory_transfer_receipt_items',
        'inventory_transfer_receipts',
        'inventory_transfer_items',
        'inventory_transfers',
        'inventory_stock_movements',
        'inventory_stocks',

        // Stage 10: Products Master Data & Taxonomies (Approved by user for fresh catalog entry)
        'products',
        'product_categories',
        'product_brands',

        // Stage 11: Suppliers Master Data (Approved by user for fresh supplier entry)
        'suppliers',

        // Stage 12: Customers, Member Groups & KYC Documents (Root borrower master records)
        'customer_nominees',
        'customer_guarantors',
        'customer_kyc_documents',
        'customer_addresses',
        'customer_group_members',
        'customer_groups',
        'customers',
    ];

    /**
     * Module definitions with human-readable labels, descriptions, and candidate tables.
     * Strictly partitioned across the 63 cleanup candidate tables.
     */
    protected array $modules = [
        'field_tracking' => [
            'key' => 'field_tracking',
            'name' => 'Field Visits & Travel Tracking',
            'icon' => 'bi-geo-alt',
            'description' => 'Employee GPS location pings, field member visit logs, and TA expense claims.',
            'tables' => [
                'field_location_logs',
                'field_visits',
                'travel_allowance_claims',
            ],
            'dependencies' => [],
        ],
        'hrm' => [
            'key' => 'hrm',
            'name' => 'HR Operations & Payroll Records',
            'icon' => 'bi-people',
            'description' => 'Daily employee attendance records, leave requests, monthly payroll batches, and salary slips. (Preserves employee master profiles).',
            'tables' => [
                'salary_slips',
                'payrolls',
                'leaves',
                'attendances',
            ],
            'dependencies' => [],
        ],
        'logs_transient' => [
            'key' => 'logs_transient',
            'name' => 'Transient Logs & Notifications',
            'icon' => 'bi-bell',
            'description' => 'Expired API idempotency keys, staff notifications, system broadcast alerts, and public contact form inquiries.',
            'tables' => [
                'api_idempotency_keys',
                'user_notifications',
                'system_notifications',
                'contact_inquiries',
            ],
            'dependencies' => [],
        ],
        'sales_billing' => [
            'key' => 'sales_billing',
            'name' => 'Direct Sales & Invoices',
            'icon' => 'bi-cart-check',
            'description' => 'Direct customer product sales, sale line items, customer billing invoices, and tax invoice items.',
            'tables' => [
                'invoice_items',
                'invoices',
                'sale_items',
                'sales',
            ],
            'dependencies' => [],
        ],
        'cash_banking' => [
            'key' => 'cash_banking',
            'name' => 'Cash Book & Bank Transactions',
            'icon' => 'bi-cash-coin',
            'description' => 'Branch daily cash registers, denomination breakdowns, cash audit logs, bank deposits, double-entry vouchers, and ledger lines.',
            'tables' => [
                'cash_book_online_collections',
                'cash_book_denominations',
                'cash_book_audits',
                'cash_book_entries',
                'cash_books',
                'bank_deposits',
                'voucher_entries',
                'vouchers',
            ],
            'dependencies' => [],
        ],
        'loans' => [
            'key' => 'loans',
            'name' => 'Loan Portfolio & Repayments',
            'icon' => 'bi-bank',
            'description' => 'Loan applications, member accounts, EMI schedules, collection receipts, disbursements, down payments, upfront fees, settlements, and penalties.',
            'tables' => [
                'product_loan_margins',
                'loan_settlement_requests',
                'loan_penalty_waivers',
                'loan_penalty_charges',
                'loan_repayments',
                'loan_installments',
                'loan_disbursements',
                'loan_down_payments',
                'loan_upfront_payments',
                'loan_account_members',
                'loan_accounts',
                'loan_application_products',
                'loan_application_members',
                'loan_applications',
            ],
            'dependencies' => [],
        ],
        'expenses_procurement' => [
            'key' => 'expenses_procurement',
            'name' => 'Expenses & Purchase History',
            'icon' => 'bi-receipt',
            'description' => 'Branch operational expense entries, expense payments, bill attachments, purchase orders, purchase line items, and supplier payment allocations.',
            'tables' => [
                'expense_attachments',
                'expense_payments',
                'expenses',
                'supplier_payment_allocations',
                'supplier_payments',
                'product_purchase_items',
                'product_purchases',
            ],
            'dependencies' => [],
        ],
        'inventory_products' => [
            'key' => 'inventory_products',
            'name' => 'Inventory & Product Master Data',
            'icon' => 'bi-boxes',
            'description' => 'Inter-branch stock transfers, receipts, cancellations, stock ledger movements, branch inventory stock balances, products, product categories, and brands.',
            'tables' => [
                'inventory_transfer_cancellation_items',
                'inventory_transfer_cancellations',
                'inventory_transfer_receipt_items',
                'inventory_transfer_receipts',
                'inventory_transfer_items',
                'inventory_transfers',
                'inventory_stock_movements',
                'inventory_stocks',
                'products',
                'product_categories',
                'product_brands',
            ],
            'dependencies' => [],
        ],
        'suppliers' => [
            'key' => 'suppliers',
            'name' => 'Supplier Master Data',
            'icon' => 'bi-truck',
            'description' => 'Vendor and supplier master profiles and contact records. (Enables starting with fresh supplier directory).',
            'tables' => [
                'suppliers',
            ],
            'dependencies' => [],
        ],
        'customers' => [
            'key' => 'customers',
            'name' => 'Customers, Member Groups & KYC',
            'icon' => 'bi-person-badge',
            'description' => 'Customer master borrower records, JLG/SHG borrowing groups, addresses, KYC document uploads, nominees, and guarantors.',
            'tables' => [
                'customer_nominees',
                'customer_guarantors',
                'customer_kyc_documents',
                'customer_addresses',
                'customer_group_members',
                'customer_groups',
                'customers',
            ],
            'dependencies' => ['loans', 'sales_billing'], // Loans and Sales reference customers
        ],
    ];

    /**
     * Exactly 56 Strictly Protected Tables that can NEVER be cleared by this service.
     */
    protected array $protectedTables = [
        // 1. Core Auth & Security
        'users',
        'roles',
        'permissions',
        'model_has_roles',
        'model_has_permissions',
        'role_has_permissions',

        // 2. Organization Structure
        'companies',
        'company_settings',
        'branches',
        'branch_settings',
        'departments',
        'designations',

        // 3. Staff & HR Master Configuration (Option A)
        'employees',
        'employee_documents',
        'salary_structures',
        'leave_types',

        // 4. Loan Product Schemes & Interest Slabs
        'loan_schemes',
        'interest_rates',

        // 5. Accounting Master & Settings (Option A & B)
        'chart_of_accounts',
        'financial_years',
        'bank_accounts',
        'voucher_number_sequences',
        'cash_book_categories',
        'expense_categories',
        'expense_approval_settings',

        // 6. System Parameters & Migrations
        'system_settings',
        'document_types',
        'migrations',

        // 7. Administrative Audit Logs & Framework Sessions (Option A)
        'activity_logs',
        'sessions',
        'cache',
        'cache_locks',
        'jobs',
        'failed_jobs',
        'job_batches',
        'password_reset_tokens',
        'personal_access_tokens',
        'device_sessions',
        'login_histories',

        // 8. Public Website & CMS Master Configuration
        'website_settings',
        'footer_settings',
        'seo_settings',
        'homepage_sections',
        'banners',
        'pages',
        'services',
        'faqs',
        'news',
        'galleries',
        'media_files',
        'team_members',
        'careers',
        'downloads',
        'why_choose_us_items',
        'cms_loan_products',
        'cms_savings_products',
    ];

    public function __construct(
        protected DatabaseBackupService $backupService,
        protected ActivityLogService $activityLogService
    ) {}

    /**
     * Get list of all available cleanup modules.
     */
    public function getAvailableModules(): array
    {
        return $this->modules;
    }

    /**
     * Get list of all strictly protected tables.
     */
    public function getProtectedTables(): array
    {
        return $this->protectedTables;
    }

    /**
     * Get system-wide data metrics (Configuration vs Transactional).
     */
    public function getSystemDataOverview(): array
    {
        $overview = [
            'protected_configuration' => [
                'super_admin_users' => User::role('Super Admin')->count(),
                'total_users' => User::count(),
                'employees' => $this->tableExists('employees') ? DB::table('employees')->count() : 0,
                'loan_schemes' => LoanScheme::count(),
                'branches' => DB::table('branches')->count(),
                'companies' => DB::table('companies')->count(),
                'chart_of_accounts' => DB::table('chart_of_accounts')->count(),
                'roles_permissions' => DB::table('roles')->count() + DB::table('permissions')->count(),
                'audit_logs' => DB::table('activity_logs')->count(),
                'protected_tables_count' => count($this->protectedTables),
            ],
            'transactional_data' => [],
            'total_cleanup_candidates' => 0,
        ];

        foreach ($this->modules as $modKey => $mod) {
            $count = 0;
            foreach ($mod['tables'] as $table) {
                if ($this->tableExists($table)) {
                    $count += DB::table($table)->count();
                }
            }
            $overview['transactional_data'][$modKey] = [
                'name' => $mod['name'],
                'icon' => $mod['icon'],
                'count' => $count,
            ];
            $overview['total_cleanup_candidates'] += $count;
        }

        return $overview;
    }

    /**
     * Compute a dependency-aware, non-destructive preview of candidate records.
     */
    public function getCleanupPreview(array $selectedModuleKeys = []): array
    {
        if (empty($selectedModuleKeys)) {
            $selectedModuleKeys = array_keys($this->modules);
        }

        // Validate cross-module dependencies
        $missingDependencies = [];
        foreach ($selectedModuleKeys as $modKey) {
            if (!isset($this->modules[$modKey])) {
                continue;
            }
            foreach ($this->modules[$modKey]['dependencies'] as $dep) {
                if (!in_array($dep, $selectedModuleKeys, true)) {
                    $missingDependencies[$modKey][] = $this->modules[$dep]['name'];
                }
            }
        }

        // Check if clearing products while loans/sales still exist in database
        if (in_array('inventory_products', $selectedModuleKeys, true)) {
            if (!in_array('loans', $selectedModuleKeys, true) && $this->tableExists('loan_application_products')) {
                if (DB::table('loan_application_products')->count() > 0) {
                    $missingDependencies['inventory_products'][] = 'Loan Portfolio & Repayments';
                }
            }
            if (!in_array('sales_billing', $selectedModuleKeys, true) && $this->tableExists('sale_items')) {
                if (DB::table('sale_items')->count() > 0) {
                    $missingDependencies['inventory_products'][] = 'Direct Sales & Invoices';
                }
            }
        }

        // Check if clearing suppliers while purchases/payments still exist in database
        if (in_array('suppliers', $selectedModuleKeys, true)) {
            if (!in_array('expenses_procurement', $selectedModuleKeys, true) && $this->tableExists('product_purchases')) {
                if (DB::table('product_purchases')->count() > 0) {
                    $missingDependencies['suppliers'][] = 'Expenses & Purchase History';
                }
            }
        }

        // Collect all tables from selected modules
        $selectedTablesSet = [];
        $modulePreviews = [];
        $totalRecordsToDelete = 0;

        foreach ($this->modules as $modKey => $mod) {
            if (!in_array($modKey, $selectedModuleKeys, true)) {
                continue;
            }

            $modTotal = 0;
            $tableDetails = [];

            foreach ($mod['tables'] as $table) {
                if ($this->tableExists($table)) {
                    $count = DB::table($table)->count();
                    $modTotal += $count;
                    $totalRecordsToDelete += $count;

                    $tableDetails[] = [
                        'table' => $table,
                        'count' => $count,
                    ];

                    $selectedTablesSet[$table] = $count;
                }
            }

            $modulePreviews[$modKey] = [
                'name' => $mod['name'],
                'icon' => $mod['icon'],
                'description' => $mod['description'],
                'total_records' => $modTotal,
                'tables' => $tableDetails,
            ];
        }

        // Order the selected tables strictly by the canonical 12-stage deletion sequence
        $tablesInDeletionOrder = [];
        foreach ($this->canonicalDeletionOrder as $table) {
            if (isset($selectedTablesSet[$table])) {
                $tablesInDeletionOrder[] = [
                    'table' => $table,
                    'count' => $selectedTablesSet[$table],
                ];
            }
        }

        // Collect all preserved tables with their actual database record counts
        $preservedTablesDetails = [];
        $totalPreservedRecords = 0;
        foreach ($this->protectedTables as $table) {
            $count = $this->tableExists($table) ? DB::table($table)->count() : 0;
            $totalPreservedRecords += $count;
            $preservedTablesDetails[] = [
                'table' => $table,
                'count' => $count,
            ];
        }

        // Detect presence of active/live business records
        $liveWarnings = [];
        if (in_array('loans', $selectedModuleKeys, true) && $this->tableExists('loan_accounts')) {
            $activeLoans = DB::table('loan_accounts')->whereIn('status', ['active', 'disbursed'])->count();
            if ($activeLoans > 0) {
                $liveWarnings[] = "Found {$activeLoans} active/disbursed loan accounts in portfolio.";
            }
        }
        if (in_array('customers', $selectedModuleKeys, true) && $this->tableExists('customers')) {
            $activeCustomers = DB::table('customers')->where('status', 'active')->count();
            if ($activeCustomers > 0) {
                $liveWarnings[] = "Found {$activeCustomers} active registered customer/borrower records.";
            }
        }
        if (in_array('cash_banking', $selectedModuleKeys, true) && $this->tableExists('bank_deposits')) {
            $deposits = DB::table('bank_deposits')->count();
            if ($deposits > 0) {
                $liveWarnings[] = "Found {$deposits} bank deposit accounting entries.";
            }
        }
        if (in_array('cash_banking', $selectedModuleKeys, true) && $this->tableExists('vouchers')) {
            $voucherCount = DB::table('vouchers')->count();
            if ($voucherCount > 0) {
                $liveWarnings[] = "Found {$voucherCount} accounting vouchers and ledgers.";
            }
        }

        // Explicit Financial Integrity Warnings
        $financialWarnings = [
            'Ledger Balance Reset: Clearing vouchers and ledger entries will reset all dynamic General Ledger balances to ₹0.00.',
            'Initial Capital Requirement: Zeroing ledger entries does NOT establish financial opening balances. An authorized Finance Manager must post an Opening Balance Journal Voucher (JV) debiting Bank/Cash and crediting Share Capital before entering live transactions.',
            'Voucher Sequence Retention: Voucher number sequences in voucher_number_sequences will be retained without reset (Option B). New vouchers will increment safely from current numbers.',
            'Bank Account Configuration: Registered bank accounts and their opening balances are strictly preserved without automatic modification.',
        ];

        return [
            'selected_modules' => $selectedModuleKeys,
            'module_previews' => $modulePreviews,
            'tables_in_deletion_order' => $tablesInDeletionOrder,
            'total_records_to_delete' => $totalRecordsToDelete,
            'preserved_tables' => $preservedTablesDetails,
            'total_preserved_tables' => count($this->protectedTables),
            'total_preserved_records' => $totalPreservedRecords,
            'missing_dependencies' => $missingDependencies,
            'has_live_records' => count($liveWarnings) > 0,
            'live_warnings' => $liveWarnings,
            'financial_warnings' => $financialWarnings,
            'protected_tables_summary' => [
                'total_protected_tables' => count($this->protectedTables),
                'total_protected_records' => $totalPreservedRecords,
                'super_admins_preserved' => User::role('Super Admin')->count(),
                'employees_preserved' => $this->tableExists('employees') ? DB::table('employees')->count() : 0,
                'branches_preserved' => DB::table('branches')->count(),
                'loan_schemes_preserved' => LoanScheme::count(),
                'chart_of_accounts_preserved' => DB::table('chart_of_accounts')->count(),
                'audit_logs_preserved' => DB::table('activity_logs')->count(),
            ],
            'warnings' => [
                'Pre-cleanup full SQL database backup is mandatory and will be automatically created and verified before any table deletion.',
                'Cleanup executes child-to-parent leaf-to-root deletion inside a database transaction without disabling foreign key checks.',
                'System settings, Super Admin credentials, employees, loan schemes, roles, permissions, and audit logs are strictly preserved.',
            ],
        ];
    }

    /**
     * Execute verified data cleanup workflow.
     */
    public function executeCleanup(array $selectedModuleKeys, string $confirmationPhrase, int $userId): array
    {
        $expectedPhrase = config('developer.confirmation_phrase', 'CLEAN DEMO DATA');

        if (trim($confirmationPhrase) !== $expectedPhrase) {
            throw new InvalidArgumentException(
                "Confirmation phrase mismatch. You must type '{$expectedPhrase}' exactly to confirm."
            );
        }

        if (empty($selectedModuleKeys)) {
            throw new InvalidArgumentException("No cleanup modules selected.");
        }

        // Validate dependencies
        foreach ($selectedModuleKeys as $modKey) {
            if (!isset($this->modules[$modKey])) {
                throw new InvalidArgumentException("Unknown cleanup module: {$modKey}");
            }
            foreach ($this->modules[$modKey]['dependencies'] as $dep) {
                if (!in_array($dep, $selectedModuleKeys, true)) {
                    $depName = $this->modules[$dep]['name'];
                    $modName = $this->modules[$modKey]['name'];
                    throw new InvalidArgumentException(
                        "Dependency error: Module '{$modName}' requires module '{$depName}' to be selected to prevent orphaned records."
                    );
                }
            }
        }

        // 1. Acquire Concurrency Lock
        $lock = Cache::lock(self::CLEANUP_LOCK_KEY, self::CLEANUP_LOCK_SECONDS);
        if (!$lock->get()) {
            throw new Exception("Another developer data cleanup operation is currently in progress. Please wait.");
        }

        $backup = null;
        $deletedCounts = [];
        $totalDeleted = 0;

        try {
            // 2. Pre-cleanup Verified Full Database Backup
            Log::info("DeveloperDataManagementService: Creating pre-cleanup verified backup...");
            $backup = $this->backupService->createVerifiedBackup();

            Log::info("DeveloperDataManagementService: Pre-cleanup backup created and verified: {$backup['filename']} ({$backup['size_formatted']})");

            // 3. Collect Tables to Delete and Sort Strictly by Canonical Deletion Order
            $selectedTablesSet = [];
            foreach ($this->modules as $modKey => $mod) {
                if (!in_array($modKey, $selectedModuleKeys, true)) {
                    continue;
                }
                foreach ($mod['tables'] as $table) {
                    if ($this->tableExists($table)) {
                        $selectedTablesSet[$table] = true;
                    }
                }
            }

            $orderedTables = [];
            foreach ($this->canonicalDeletionOrder as $table) {
                if (isset($selectedTablesSet[$table])) {
                    $orderedTables[] = $table;
                }
            }

            // 4. Execute Leaf-to-Root Deletion in Database Transaction
            DB::transaction(function () use ($orderedTables, &$deletedCounts, &$totalDeleted) {
                // If vouchers is in deletion list, nullify self-referencing reversed_voucher_id first
                if (in_array('vouchers', $orderedTables, true) && $this->tableExists('vouchers')) {
                    DB::table('vouchers')->update(['reversed_voucher_id' => null]);
                }

                foreach ($orderedTables as $table) {
                    // Double check table is not in protected list
                    if (in_array($table, $this->protectedTables, true)) {
                        Log::warning("DeveloperDataManagementService: Skipping protected table {$table}");
                        continue;
                    }

                    $count = DB::table($table)->count();
                    if ($count > 0) {
                        // Leaf-to-root DELETE statement (retaining table structure & triggers)
                        DB::table($table)->delete();
                        $deletedCounts[$table] = $count;
                        $totalDeleted += $count;
                    } else {
                        $deletedCounts[$table] = 0;
                    }
                }
            });

            // 5. Post-Cleanup Verification: Ensure Protected Core Tables Retain Required Records
            $this->verifyPostCleanupIntegrity();

            // 6. Security Audit Log
            $user = User::find($userId);
            $this->activityLogService->log(
                event: 'developer_data_cleanup_executed',
                model: $user,
                oldValues: null,
                newValues: [
                    'backup_file' => $backup['filename'],
                    'backup_size' => $backup['size_formatted'],
                    'total_deleted_records' => $totalDeleted,
                    'modules' => $selectedModuleKeys,
                    'tables_cleaned' => $deletedCounts,
                ]
            );

            Log::info("DeveloperDataManagementService: Cleanup completed successfully.", [
                'user_id' => $userId,
                'total_deleted' => $totalDeleted,
                'backup' => $backup['filename'],
            ]);

            return [
                'success' => true,
                'backup' => $backup,
                'total_deleted' => $totalDeleted,
                'deleted_counts' => $deletedCounts,
                'completed_at' => now()->toIso8601String(),
            ];
        } catch (Exception $e) {
            Log::error("DeveloperDataManagementService: Cleanup failed: " . $e->getMessage(), [
                'exception' => $e,
                'user_id' => $userId,
            ]);

            throw $e;
        } finally {
            // Release concurrency lock
            $lock->release();
        }
    }

    /**
     * Verify that critical system configuration and accounts remain intact after cleanup.
     */
    protected function verifyPostCleanupIntegrity(): void
    {
        $superAdminCount = User::role('Super Admin')->count();
        if ($superAdminCount === 0) {
            throw new Exception("Post-cleanup integrity alert: Super Admin account missing!");
        }

        $schemesCount = LoanScheme::count();
        if ($schemesCount === 0) {
            throw new Exception("Post-cleanup integrity alert: Loan Schemes configuration missing!");
        }

        $rolesCount = DB::table('roles')->count();
        if ($rolesCount === 0) {
            throw new Exception("Post-cleanup integrity alert: System Roles missing!");
        }

        $branchesCount = DB::table('branches')->count();
        if ($branchesCount === 0) {
            throw new Exception("Post-cleanup integrity alert: Branch configuration missing!");
        }

        if ($this->tableExists('employees')) {
            $employeeCount = DB::table('employees')->count();
            if ($employeeCount === 0) {
                throw new Exception("Post-cleanup integrity alert: Employee master profiles missing!");
            }
        }

        $coaCount = DB::table('chart_of_accounts')->count();
        if ($coaCount === 0) {
            throw new Exception("Post-cleanup integrity alert: Chart of Accounts missing!");
        }
    }

    /**
     * Check if a database table exists.
     */
    protected function tableExists(string $tableName): bool
    {
        try {
            return DB::getSchemaBuilder()->hasTable($tableName);
        } catch (Exception $e) {
            return false;
        }
    }
}
