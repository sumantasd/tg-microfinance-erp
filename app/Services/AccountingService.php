<?php

namespace App\Services;

use App\Models\BankAccount;
use App\Models\Branch;
use App\Models\ChartOfAccount;
use App\Models\Company;
use App\Models\FinancialYear;
use App\Models\LoanAccount;
use App\Models\LoanDisbursement;
use App\Models\LoanDownPayment;
use App\Models\LoanRepayment;
use App\Models\User;
use App\Models\Voucher;
use App\Models\VoucherEntry;
use App\Models\VoucherNumberSequence;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class AccountingService
{
    public function __construct(protected ActivityLogService $activityLogService) {}

    /**
     * Seed Default Micro Finance Chart of Accounts for a Company.
     */
    public function seedDefaultChartOfAccounts(Company|int $company, ?int $userId = null): void
    {
        $companyId = is_numeric($company) ? $company : $company->id;
        $user = $userId ?: Auth::id();

        $defaultAccounts = [
            // 1000 - ASSETS
            ['account_code' => '1000', 'account_name' => 'Assets', 'account_type' => 'asset', 'account_group' => 'asset_header', 'parent_code' => null, 'is_system' => true],
            ['account_code' => '1100', 'account_name' => 'Cash & Bank Balances', 'account_type' => 'asset', 'account_group' => 'current_asset', 'parent_code' => '1000', 'is_system' => true],
            ['account_code' => '1110', 'account_name' => 'Branch Cash Vault', 'account_type' => 'asset', 'account_group' => 'current_asset', 'parent_code' => '1100', 'is_system' => true],
            ['account_code' => '1120', 'account_name' => 'Cash in Transit / Field Collection', 'account_type' => 'asset', 'account_group' => 'current_asset', 'parent_code' => '1100', 'is_system' => true],
            ['account_code' => '1130', 'account_name' => 'Bank Operating Accounts', 'account_type' => 'asset', 'account_group' => 'current_asset', 'parent_code' => '1100', 'is_system' => true],
            ['account_code' => '1200', 'account_name' => 'Loans & Advances (Portfolio)', 'account_type' => 'asset', 'account_group' => 'loan_portfolio', 'parent_code' => '1000', 'is_system' => true],
            ['account_code' => '1210', 'account_name' => 'Cash Microfinance Loans Receivable', 'account_type' => 'asset', 'account_group' => 'loan_portfolio', 'parent_code' => '1200', 'is_system' => true],
            ['account_code' => '1220', 'account_name' => 'Product Loans Receivable (Financed Principal)', 'account_type' => 'asset', 'account_group' => 'loan_portfolio', 'parent_code' => '1200', 'is_system' => true],
            ['account_code' => '1290', 'account_name' => 'Allowance for Loan Impairment / Bad Debts', 'account_type' => 'asset', 'account_group' => 'loan_portfolio', 'parent_code' => '1200', 'is_system' => true],
            ['account_code' => '1300', 'account_name' => 'Inventory & Stock Assets', 'account_type' => 'asset', 'account_group' => 'inventory_asset', 'parent_code' => '1000', 'is_system' => true],
            ['account_code' => '1310', 'account_name' => 'Product Inventory on Hand', 'account_type' => 'asset', 'account_group' => 'inventory_asset', 'parent_code' => '1300', 'is_system' => true],
            ['account_code' => '1320', 'account_name' => 'Goods in Transit (Stock Transfers)', 'account_type' => 'asset', 'account_group' => 'inventory_asset', 'parent_code' => '1300', 'is_system' => true],
            ['account_code' => '1400', 'account_name' => 'Other Current Assets & Tax Credits', 'account_type' => 'asset', 'account_group' => 'current_asset', 'parent_code' => '1000', 'is_system' => true],
            ['account_code' => '1410', 'account_name' => 'Input GST Tax Credit Receivable', 'account_type' => 'asset', 'account_group' => 'current_asset', 'parent_code' => '1400', 'is_system' => true],
            ['account_code' => '1500', 'account_name' => 'Property, Plant & Equipment (Fixed Assets)', 'account_type' => 'asset', 'account_group' => 'fixed_asset', 'parent_code' => '1000', 'is_system' => true],

            // 2000 - LIABILITIES
            ['account_code' => '2000', 'account_name' => 'Liabilities', 'account_type' => 'liability', 'account_group' => 'liability_header', 'parent_code' => null, 'is_system' => true],
            ['account_code' => '2100', 'account_name' => 'Current Liabilities & Payables', 'account_type' => 'liability', 'account_group' => 'current_liability', 'parent_code' => '2000', 'is_system' => true],
            ['account_code' => '2110', 'account_name' => 'Accounts Payable / Supplier Liabilities', 'account_type' => 'liability', 'account_group' => 'current_liability', 'parent_code' => '2100', 'is_system' => true],
            ['account_code' => '2120', 'account_name' => 'Customer Down Payment / Advance Clearing', 'account_type' => 'liability', 'account_group' => 'current_liability', 'parent_code' => '2100', 'is_system' => true],
            ['account_code' => '2130', 'account_name' => 'Salary & Wages Payable', 'account_type' => 'liability', 'account_group' => 'current_liability', 'parent_code' => '2100', 'is_system' => true],
            ['account_code' => '2140', 'account_name' => 'Employee Statutory Deductions (PF/ESI) Payable', 'account_type' => 'liability', 'account_group' => 'current_liability', 'parent_code' => '2100', 'is_system' => true],
            ['account_code' => '2150', 'account_name' => 'TDS / Tax Withholding Payable', 'account_type' => 'liability', 'account_group' => 'current_liability', 'parent_code' => '2100', 'is_system' => true],
            ['account_code' => '2200', 'account_name' => 'Long Term Borrowings & Lines of Credit', 'account_type' => 'liability', 'account_group' => 'long_term_liability', 'parent_code' => '2000', 'is_system' => true],

            // 3000 - EQUITY
            ['account_code' => '3000', 'account_name' => 'Equity & Capital Funds', 'account_type' => 'equity', 'account_group' => 'equity_header', 'parent_code' => null, 'is_system' => true],
            ['account_code' => '3100', 'account_name' => 'Share Capital / Promoters Equity', 'account_type' => 'equity', 'account_group' => 'equity', 'parent_code' => '3000', 'is_system' => true],
            ['account_code' => '3200', 'account_name' => 'Retained Earnings (Prior Years Surplus)', 'account_type' => 'equity', 'account_group' => 'equity', 'parent_code' => '3000', 'is_system' => true],
            ['account_code' => '3300', 'account_name' => 'Current Year Profit / Loss Transfer', 'account_type' => 'equity', 'account_group' => 'equity', 'parent_code' => '3000', 'is_system' => true],

            // 4000 - REVENUE
            ['account_code' => '4000', 'account_name' => 'Operating & Non-Operating Income', 'account_type' => 'revenue', 'account_group' => 'revenue_header', 'parent_code' => null, 'is_system' => true],
            ['account_code' => '4100', 'account_name' => 'Interest Income on Loan Portfolio', 'account_type' => 'revenue', 'account_group' => 'operating_revenue', 'parent_code' => '4000', 'is_system' => true],
            ['account_code' => '4110', 'account_name' => 'Interest Income from Cash Loans', 'account_type' => 'revenue', 'account_group' => 'operating_revenue', 'parent_code' => '4100', 'is_system' => true],
            ['account_code' => '4120', 'account_name' => 'Interest Income from Product Loans', 'account_type' => 'revenue', 'account_group' => 'operating_revenue', 'parent_code' => '4100', 'is_system' => true],
            ['account_code' => '4200', 'account_name' => 'Fees, Service & Penalty Income', 'account_type' => 'revenue', 'account_group' => 'fee_income', 'parent_code' => '4000', 'is_system' => true],
            ['account_code' => '4210', 'account_name' => 'Loan Processing Fee Income', 'account_type' => 'revenue', 'account_group' => 'fee_income', 'parent_code' => '4200', 'is_system' => true],
            ['account_code' => '4220', 'account_name' => 'Insurance Administration Fee Income', 'account_type' => 'revenue', 'account_group' => 'fee_income', 'parent_code' => '4200', 'is_system' => true],
            ['account_code' => '4230', 'account_name' => 'Penalties & Late Overdue Charges', 'account_type' => 'revenue', 'account_group' => 'fee_income', 'parent_code' => '4200', 'is_system' => true],
            ['account_code' => '4240', 'account_name' => 'Foreclosure & Prepayment Fee Income', 'account_type' => 'revenue', 'account_group' => 'fee_income', 'parent_code' => '4200', 'is_system' => true],
            ['account_code' => '4300', 'account_name' => 'Product Loan Sales Revenue', 'account_type' => 'revenue', 'account_group' => 'operating_revenue', 'parent_code' => '4000', 'is_system' => true],
            ['account_code' => '4310', 'account_name' => 'Product Loan Retail Sales Revenue', 'account_type' => 'revenue', 'account_group' => 'operating_revenue', 'parent_code' => '4300', 'is_system' => true],

            // 5000 - EXPENSES
            ['account_code' => '5000', 'account_name' => 'Operating & Administrative Expenses', 'account_type' => 'expense', 'account_group' => 'expense_header', 'parent_code' => null, 'is_system' => true],
            ['account_code' => '5100', 'account_name' => 'Cost of Sales & Direct Lending Costs', 'account_type' => 'expense', 'account_group' => 'direct_expense', 'parent_code' => '5000', 'is_system' => true],
            ['account_code' => '5110', 'account_name' => 'Cost of Goods Sold (COGS - Product Loans)', 'account_type' => 'expense', 'account_group' => 'direct_expense', 'parent_code' => '5100', 'is_system' => true],
            ['account_code' => '5120', 'account_name' => 'Loan Loss Provisioning & Write-Offs', 'account_type' => 'expense', 'account_group' => 'direct_expense', 'parent_code' => '5100', 'is_system' => true],
            ['account_code' => '5200', 'account_name' => 'Personnel & Payroll Expenses', 'account_type' => 'expense', 'account_group' => 'administrative_expense', 'parent_code' => '5000', 'is_system' => true],
            ['account_code' => '5210', 'account_name' => 'Salaries, Wages & Allowances Expense', 'account_type' => 'expense', 'account_group' => 'administrative_expense', 'parent_code' => '5200', 'is_system' => true],
            ['account_code' => '5220', 'account_name' => 'Employer Statutory Contributions (PF/ESI)', 'account_type' => 'expense', 'account_group' => 'administrative_expense', 'parent_code' => '5200', 'is_system' => true],
            ['account_code' => '5300', 'account_name' => 'Branch Administrative & Overhead Expenses', 'account_type' => 'expense', 'account_group' => 'administrative_expense', 'parent_code' => '5000', 'is_system' => true],
            ['account_code' => '5310', 'account_name' => 'Branch Office Rent', 'account_type' => 'expense', 'account_group' => 'administrative_expense', 'parent_code' => '5300', 'is_system' => true],
            ['account_code' => '5320', 'account_name' => 'Electricity, Power & Utilities', 'account_type' => 'expense', 'account_group' => 'administrative_expense', 'parent_code' => '5300', 'is_system' => true],
            ['account_code' => '5330', 'account_name' => 'Printing, Stationary & Thermal Rolls', 'account_type' => 'expense', 'account_group' => 'administrative_expense', 'parent_code' => '5300', 'is_system' => true],
            ['account_code' => '5340', 'account_name' => 'Travel, Conveyance & Field Agent Fuel', 'account_type' => 'expense', 'account_group' => 'administrative_expense', 'parent_code' => '5300', 'is_system' => true],
            ['account_code' => '5350', 'account_name' => 'Bank Charges, Gateway & Collection Fees', 'account_type' => 'expense', 'account_group' => 'administrative_expense', 'parent_code' => '5300', 'is_system' => true],
        ];

        DB::transaction(function () use ($companyId, $user, $defaultAccounts) {
            $codeToId = [];

            foreach ($defaultAccounts as $acc) {
                $parentId = !empty($acc['parent_code']) && isset($codeToId[$acc['parent_code']])
                    ? $codeToId[$acc['parent_code']]
                    : null;

                $record = ChartOfAccount::firstOrCreate(
                    [
                        'company_id' => $companyId,
                        'account_code' => $acc['account_code'],
                    ],
                    [
                        'account_name' => $acc['account_name'],
                        'account_type' => $acc['account_type'],
                        'account_group' => $acc['account_group'],
                        'parent_id' => $parentId,
                        'is_system' => $acc['is_system'],
                        'is_active' => true,
                        'created_by' => $user,
                        'updated_by' => $user,
                    ]
                );

                $codeToId[$acc['account_code']] = $record->id;
            }
        });
    }

    /**
     * Resolve Financial Year for a specific date and ensure it is open.
     */
    public function getAndValidateFinancialYear(int $companyId, string|Carbon $date): FinancialYear
    {
        $d = Carbon::parse($date);
        $fy = FinancialYear::forDate($companyId, $d);

        if (!$fy) {
            // Auto-initialize standard Indian Financial Year (April 1 to March 31) if none exists
            $startYear = $d->month >= 4 ? $d->year : $d->year - 1;
            $endYear = $startYear + 1;
            $startDate = "{$startYear}-04-01";
            $endDate = "{$endYear}-03-31";
            $title = "FY {$startYear}-{$endYear}";

            $fy = FinancialYear::firstOrCreate(
                [
                    'company_id' => $companyId,
                    'start_date' => $startDate,
                    'end_date' => $endDate,
                ],
                [
                    'title' => $title,
                    'is_closed' => false,
                    'created_by' => Auth::id(),
                    'updated_by' => Auth::id(),
                ]
            );
        }

        if ($fy->is_closed) {
            throw ValidationException::withMessages([
                'voucher_date' => "Financial year '{$fy->title}' ({$fy->start_date->format('d M Y')} - {$fy->end_date->format('d M Y')}) is closed. No new transactions or modifications are permitted.",
            ]);
        }

        return $fy;
    }

    /**
     * Create Double-Entry Journal Voucher.
     * Enforces Strict Balance: Sum(Debits) === Sum(Credits).
     */
    public function createVoucher(array $voucherData, array $entries, bool $posted = true): Voucher
    {
        if (count($entries) < 2) {
            throw ValidationException::withMessages(['entries' => 'A valid double-entry voucher requires at least two lines (at least one Debit and one Credit).']);
        }

        $totalDebit = 0.00;
        $totalCredit = 0.00;
        $formattedEntries = [];

        foreach ($entries as $idx => $entry) {
            $debit = round((float) ($entry['debit'] ?? 0), 2);
            $credit = round((float) ($entry['credit'] ?? 0), 2);

            if ($debit < 0 || $credit < 0) {
                throw ValidationException::withMessages(["entries.{$idx}" => 'Debit and Credit amounts cannot be negative.']);
            }

            if ($debit == 0 && $credit == 0) {
                continue; // Skip zero value rows
            }

            if ($debit > 0 && $credit > 0) {
                throw ValidationException::withMessages(["entries.{$idx}" => 'A single entry line cannot have both Debit and Credit amounts.']);
            }

            $accountId = (int) $entry['account_id'];
            $account = ChartOfAccount::find($accountId);
            if (!$account) {
                throw ValidationException::withMessages(["entries.{$idx}.account_id" => 'Selected ledger account does not exist.']);
            }

            if ($account->company_id !== (int) $voucherData['company_id']) {
                throw ValidationException::withMessages(["entries.{$idx}.account_id" => "Account '{$account->account_name}' does not belong to the selected company."]);
            }

            $totalDebit += $debit;
            $totalCredit += $credit;

            $formattedEntries[] = [
                'account_id' => $accountId,
                'debit' => $debit,
                'credit' => $credit,
                'description' => $entry['description'] ?? null,
            ];
        }

        $totalDebit = round($totalDebit, 2);
        $totalCredit = round($totalCredit, 2);

        // Strict Balance Invariant Check
        if (abs($totalDebit - $totalCredit) > 0.001 || $totalDebit <= 0) {
            throw ValidationException::withMessages([
                'entries' => "Double-entry out of balance! Total Debits (₹" . number_format($totalDebit, 2) . ") must equal Total Credits (₹" . number_format($totalCredit, 2) . "). Difference: ₹" . number_format(abs($totalDebit - $totalCredit), 2),
            ]);
        }

        return DB::transaction(function () use ($voucherData, $formattedEntries, $totalDebit, $totalCredit, $posted) {
            $companyId = (int) $voucherData['company_id'];
            $branchId = (int) $voucherData['branch_id'];
            $voucherDate = $voucherData['voucher_date'] ?? now()->toDateString();
            $voucherType = $voucherData['voucher_type'] ?? 'journal';

            // Validate Financial Year
            $fy = $this->getAndValidateFinancialYear($companyId, $voucherDate);

            // Generate sequential voucher number
            $prefix = match($voucherType) {
                'receipt' => 'RV',
                'payment' => 'PV',
                'contra' => 'CV',
                default => 'JV',
            };

            $voucherNumber = VoucherNumberSequence::generateNextVoucherNumber($companyId, $branchId, $prefix);

            $voucher = Voucher::create([
                'company_id' => $companyId,
                'branch_id' => $branchId,
                'financial_year_id' => $fy->id,
                'voucher_number' => $voucherNumber,
                'voucher_type' => $voucherType,
                'voucher_date' => $voucherDate,
                'total_debit' => $totalDebit,
                'total_credit' => $totalCredit,
                'narration' => $voucherData['narration'] ?? null,
                'status' => $posted ? 'posted' : ($voucherData['status'] ?? 'posted'),
                'is_reversal' => $voucherData['is_reversal'] ?? false,
                'reversed_voucher_id' => $voucherData['reversed_voucher_id'] ?? null,
                'reversal_reason' => $voucherData['reversal_reason'] ?? null,
                'reference_type' => $voucherData['reference_type'] ?? null,
                'reference_id' => $voucherData['reference_id'] ?? null,
                'created_by' => Auth::id(),
                'updated_by' => Auth::id(),
            ]);

            foreach ($formattedEntries as $item) {
                $voucher->entries()->create($item);
            }

            $this->activityLogService->log('voucher_posted', $voucher, null, [
                'voucher_number' => $voucher->voucher_number,
                'total_amount' => $voucher->total_debit,
                'type' => $voucher->voucher_type,
            ]);

            return $voucher;
        });
    }

    /**
     * Create Reversal Voucher for an existing Posted Voucher.
     * Swaps Debits and Credits atomically.
     */
    public function reverseVoucher(Voucher|int $voucher, string $reversalReason, ?string $reversalDate = null, ?int $userId = null): Voucher
    {
        $original = is_numeric($voucher) ? Voucher::with('entries')->findOrFail($voucher) : $voucher;

        if ($original->status !== 'posted') {
            throw ValidationException::withMessages(['status' => "Cannot reverse voucher '{$original->voucher_number}' with status '{$original->status}'."]);
        }

        if ($original->is_reversal) {
            throw ValidationException::withMessages(['status' => 'Cannot reverse a reversal voucher.']);
        }

        $existingReversal = Voucher::where('reversed_voucher_id', $original->id)->first();
        if ($existingReversal) {
            throw ValidationException::withMessages(['status' => "Voucher '{$original->voucher_number}' has already been reversed by '{$existingReversal->voucher_number}'."]);
        }

        $rDate = $reversalDate ? Carbon::parse($reversalDate)->toDateString() : now()->toDateString();
        $this->getAndValidateFinancialYear($original->company_id, $rDate);

        return DB::transaction(function () use ($original, $reversalReason, $rDate, $userId) {
            $reversalEntries = [];
            foreach ($original->entries as $line) {
                $reversalEntries[] = [
                    'account_id' => $line->account_id,
                    'debit' => $line->credit,   // Swapped
                    'credit' => $line->debit,   // Swapped
                    'description' => "Reversal: " . ($line->description ?: "Original line #{$line->id}"),
                ];
            }

            $voucherData = [
                'company_id' => $original->company_id,
                'branch_id' => $original->branch_id,
                'voucher_type' => 'journal',
                'voucher_date' => $rDate,
                'narration' => "Reversal of Voucher {$original->voucher_number}. Reason: {$reversalReason}",
                'is_reversal' => true,
                'reversed_voucher_id' => $original->id,
                'reversal_reason' => $reversalReason,
                'reference_type' => $original->reference_type,
                'reference_id' => $original->reference_id,
            ];

            $reversalVoucher = $this->createVoucher($voucherData, $reversalEntries, true);

            $this->activityLogService->log('voucher_reversed', $original, null, [
                'reversal_voucher_id' => $reversalVoucher->id,
                'reversal_voucher_number' => $reversalVoucher->voucher_number,
                'reason' => $reversalReason,
            ]);

            return $reversalVoucher;
        });
    }

    /**
     * Resolve GL Account for Payment Method (Cash, Bank Transfer, UPI, Cheque).
     */
    public function resolvePaymentMethodAccount(int $companyId, ?int $branchId, string $paymentMethod): ChartOfAccount
    {
        // 1. If Cash
        if ($paymentMethod === 'cash') {
            $account = ChartOfAccount::where('company_id', $companyId)
                ->where('account_code', '1110')
                ->first();

            if (!$account) {
                $this->seedDefaultChartOfAccounts($companyId);
                $account = ChartOfAccount::where('company_id', $companyId)
                    ->where('account_code', '1110')
                    ->first();
            }

            if ($account) {
                return $account;
            }
        }

        // 2. If Bank Transfer, UPI, or Cheque
        if (in_array($paymentMethod, ['bank_transfer', 'upi', 'cheque'])) {
            // Check if specific BankAccount exists for this branch/company with linked GL account
            if ($branchId) {
                $bankAccount = BankAccount::where('company_id', $companyId)
                    ->where('branch_id', $branchId)
                    ->where('is_active', true)
                    ->whereNotNull('chart_of_account_id')
                    ->first();

                if ($bankAccount && $bankAccount->chartOfAccount) {
                    return $bankAccount->chartOfAccount;
                }
            }

            // Check for HO / general bank account
            $hoBankAccount = BankAccount::where('company_id', $companyId)
                ->where('is_active', true)
                ->whereNotNull('chart_of_account_id')
                ->first();

            if ($hoBankAccount && $hoBankAccount->chartOfAccount) {
                return $hoBankAccount->chartOfAccount;
            }

            // Fallback to default Bank Operating Account (1130)
            $account = ChartOfAccount::where('company_id', $companyId)
                ->where('account_code', '1130')
                ->first();

            if (!$account) {
                $this->seedDefaultChartOfAccounts($companyId);
                $account = ChartOfAccount::where('company_id', $companyId)
                    ->where('account_code', '1130')
                    ->first();
            }

            if ($account) {
                return $account;
            }
        }

        // 3. Fallback to default Cash Vault if unspecified
        $fallback = ChartOfAccount::where('company_id', $companyId)
            ->where('account_code', '1110')
            ->first();

        if (!$fallback) {
            $this->seedDefaultChartOfAccounts($companyId);
            $fallback = ChartOfAccount::where('company_id', $companyId)
                ->where('account_code', '1110')
                ->first();
        }

        if (!$fallback) {
            throw ValidationException::withMessages([
                'payment_method' => "Could not resolve an active General Ledger account for payment method '{$paymentMethod}'.",
            ]);
        }

        return $fallback;
    }

    /**
     * Post Automatic General Ledger Voucher for Cash Loan Disbursement.
     * IDEMPOTENT: If a voucher already exists for this disbursement, it is returned without duplicate posting.
     */
    public function postCashLoanDisbursement(LoanAccount $loanAccount, LoanDisbursement $disbursement): ?Voucher
    {
        // 1. Idempotency Check
        $existing = Voucher::where('reference_type', 'loan_disbursement')
            ->where('reference_id', $disbursement->id)
            ->first();

        if ($existing) {
            return $existing;
        }

        // 2. Guard: Only Cash Loans in Phase 2A
        if ($loanAccount->loan_type !== 'cash') {
            return null;
        }

        $disbursedAmount = round((float) $disbursement->disbursed_amount, 2);
        if ($disbursedAmount <= 0) {
            return null;
        }

        // Ensure COA exists
        $loanPrincipalAccount = ChartOfAccount::where('company_id', $loanAccount->company_id)
            ->where('account_code', '1210')
            ->first();

        if (!$loanPrincipalAccount) {
            $this->seedDefaultChartOfAccounts($loanAccount->company_id);
            $loanPrincipalAccount = ChartOfAccount::where('company_id', $loanAccount->company_id)
                ->where('account_code', '1210')
                ->first();
        }

        $creditAccount = $this->resolvePaymentMethodAccount(
            $loanAccount->company_id,
            $loanAccount->branch_id,
            $disbursement->payment_method
        );

        $customerName = $loanAccount->customer ? $loanAccount->customer->full_name : 'Borrower';
        $pDate = $disbursement->disbursement_date
            ? Carbon::parse($disbursement->disbursement_date)->toDateString()
            : now()->toDateString();

        $voucherData = [
            'company_id' => $loanAccount->company_id,
            'branch_id' => $loanAccount->branch_id,
            'voucher_type' => 'payment',
            'voucher_date' => $pDate,
            'narration' => "Cash loan disbursement for Loan #{$loanAccount->loan_number} (Customer: {$customerName})",
            'reference_type' => 'loan_disbursement',
            'reference_id' => $disbursement->id,
        ];

        $entries = [
            [
                'account_id' => $loanPrincipalAccount->id,
                'debit' => $disbursedAmount,
                'credit' => 0.00,
                'description' => "Principal Disbursed - Loan #{$loanAccount->loan_number}",
            ],
            [
                'account_id' => $creditAccount->id,
                'debit' => 0.00,
                'credit' => $disbursedAmount,
                'description' => "Disbursement Payout via " . ucfirst(str_replace('_', ' ', $disbursement->payment_method)),
            ],
        ];

        return $this->createVoucher($voucherData, $entries, true);
    }

    /**
     * Post Automatic General Ledger Voucher for Loan Repayment / EMI Collection.
     * IDEMPOTENT: If a voucher already exists for this repayment, it is returned without duplicate posting.
     */
    public function postLoanRepayment(LoanRepayment $repayment, ?LoanAccount $loanAccount = null): ?Voucher
    {
        // 1. Idempotency Check
        $existing = Voucher::where('reference_type', 'loan_repayment')
            ->where('reference_id', $repayment->id)
            ->first();

        if ($existing) {
            return $existing;
        }

        $totalAmount = round((float) $repayment->amount, 2);
        if ($totalAmount <= 0) {
            return null;
        }

        $loan = $loanAccount ?: $repayment->loanAccount;
        if (!$loan) {
            $loan = LoanAccount::find($repayment->loan_account_id);
        }

        // Ensure default accounts exist for company
        $principalCode = ($loan && $loan->loan_type === 'product') ? '1220' : '1210';
        $interestCode = ($loan && $loan->loan_type === 'product') ? '4120' : '4110';

        $principalAccount = ChartOfAccount::where('company_id', $loan->company_id)->where('account_code', $principalCode)->first();
        if (!$principalAccount) {
            $this->seedDefaultChartOfAccounts($loan->company_id);
            $principalAccount = ChartOfAccount::where('company_id', $loan->company_id)->where('account_code', $principalCode)->first();
        }

        $interestAccount = ChartOfAccount::where('company_id', $loan->company_id)->where('account_code', $interestCode)->first();
        $feeAccount = ChartOfAccount::where('company_id', $loan->company_id)->where('account_code', '4210')->first();
        $penaltyAccount = ChartOfAccount::where('company_id', $loan->company_id)->where('account_code', '4230')->first();

        $debitAccount = $this->resolvePaymentMethodAccount(
            $loan->company_id,
            $loan->branch_id,
            $repayment->payment_method
        );

        $principalPaid = round((float) $repayment->principal_paid, 2);
        $interestPaid = round((float) $repayment->interest_paid, 2);
        $feePaid = round((float) $repayment->fee_paid, 2);
        $penaltyPaid = round((float) $repayment->penalty_paid, 2);

        // Fallback: If breakdown sum is less than total amount, allocate difference to principal
        $breakdownSum = round($principalPaid + $interestPaid + $feePaid + $penaltyPaid, 2);
        if ($breakdownSum < $totalAmount) {
            $principalPaid = round($principalPaid + ($totalAmount - $breakdownSum), 2);
        }

        $entries = [];

        // 1. Debit Line (Receipt via Cash/Bank/UPI)
        $entries[] = [
            'account_id' => $debitAccount->id,
            'debit' => $totalAmount,
            'credit' => 0.00,
            'description' => "Repayment Receipt #{$repayment->receipt_number} via " . ucfirst(str_replace('_', ' ', $repayment->payment_method)),
        ];

        // 2. Credit Lines for Waterfall
        if ($principalPaid > 0) {
            $entries[] = [
                'account_id' => $principalAccount->id,
                'debit' => 0.00,
                'credit' => $principalPaid,
                'description' => "Principal Collection - Loan #{$loan->loan_number}",
            ];
        }

        if ($interestPaid > 0 && $interestAccount) {
            $entries[] = [
                'account_id' => $interestAccount->id,
                'debit' => 0.00,
                'credit' => $interestPaid,
                'description' => "Interest Income - Loan #{$loan->loan_number}",
            ];
        }

        if ($feePaid > 0 && $feeAccount) {
            $entries[] = [
                'account_id' => $feeAccount->id,
                'debit' => 0.00,
                'credit' => $feePaid,
                'description' => "Fee Recovery - Loan #{$loan->loan_number}",
            ];
        }

        if ($penaltyPaid > 0 && $penaltyAccount) {
            $entries[] = [
                'account_id' => $penaltyAccount->id,
                'debit' => 0.00,
                'credit' => $penaltyPaid,
                'description' => "Penalty Income - Loan #{$loan->loan_number}",
            ];
        }

        // If only 1 line was added (e.g. no sub components > 0), credit full amount to principal
        if (count($entries) < 2) {
            $entries[] = [
                'account_id' => $principalAccount->id,
                'debit' => 0.00,
                'credit' => $totalAmount,
                'description' => "Loan Repayment - Loan #{$loan->loan_number}",
            ];
        }

        $customerName = $loan->customer ? $loan->customer->full_name : 'Borrower';
        $pDate = $repayment->payment_date
            ? Carbon::parse($repayment->payment_date)->toDateString()
            : now()->toDateString();

        $voucherData = [
            'company_id' => $loan->company_id,
            'branch_id' => $loan->branch_id,
            'voucher_type' => 'receipt',
            'voucher_date' => $pDate,
            'narration' => "EMI Repayment for Loan #{$loan->loan_number} (Customer: {$customerName}, Receipt: {$repayment->receipt_number})",
            'reference_type' => 'loan_repayment',
            'reference_id' => $repayment->id,
        ];

        return $this->createVoucher($voucherData, $entries, true);
    }

    /**
     * Post Automatic General Ledger Voucher for Product Loan Down Payment.
     * IDEMPOTENT: If a voucher already exists for this down payment, it is returned without duplicate posting.
     */
    public function postProductLoanDownPayment(LoanDownPayment $downPayment, ?LoanAccount $loanAccount = null): ?Voucher
    {
        // 1. Idempotency Check
        $existing = Voucher::where('reference_type', 'loan_down_payment')
            ->where('reference_id', $downPayment->id)
            ->first();

        if ($existing) {
            return $existing;
        }

        $amount = round((float) $downPayment->amount, 2);
        if ($amount <= 0) {
            return null;
        }

        $loan = $loanAccount ?: $downPayment->loanAccount;
        if (!$loan) {
            $loan = LoanAccount::find($downPayment->loan_account_id);
        }

        $companyId = $loan->company_id;
        $branchId = $loan->branch_id;

        // Debit: Cash Vault (1110) or Bank Operating Account (1130)
        $debitAccount = $this->resolvePaymentMethodAccount(
            $companyId,
            $branchId,
            $downPayment->payment_method ?? 'cash'
        );

        // Credit: 2120 - Customer Down Payment / Advance Clearing
        $clearingAccount = ChartOfAccount::where('company_id', $companyId)
            ->where('account_code', '2120')
            ->first();

        if (!$clearingAccount) {
            $this->seedDefaultChartOfAccounts($companyId);
            $clearingAccount = ChartOfAccount::where('company_id', $companyId)
                ->where('account_code', '2120')
                ->first();
        }

        if (!$clearingAccount) {
            throw ValidationException::withMessages([
                'down_payment' => 'Could not resolve Customer Down Payment Clearing GL account (2120).',
            ]);
        }

        $customerName = $loan->customer ? $loan->customer->full_name : 'Borrower';
        $pDate = $downPayment->payment_date
            ? Carbon::parse($downPayment->payment_date)->toDateString()
            : now()->toDateString();

        $voucherData = [
            'company_id' => $companyId,
            'branch_id' => $branchId,
            'voucher_type' => 'receipt',
            'voucher_date' => $pDate,
            'narration' => "Down payment received for Product Loan #{$loan->loan_number} (Customer: {$customerName})",
            'reference_type' => 'loan_down_payment',
            'reference_id' => $downPayment->id,
        ];

        $entries = [
            [
                'account_id' => $debitAccount->id,
                'debit' => $amount,
                'credit' => 0.00,
                'description' => "Down payment received via " . ucfirst(str_replace('_', ' ', $downPayment->payment_method ?? 'cash')),
            ],
            [
                'account_id' => $clearingAccount->id,
                'debit' => 0.00,
                'credit' => $amount,
                'description' => "Customer down payment advance - Loan #{$loan->loan_number}",
            ],
        ];

        return $this->createVoucher($voucherData, $entries, true);
    }

    /**
     * Post Automatic General Ledger Voucher for Product Loan Issue & Physical Stock Fulfillment.
     * IDEMPOTENT: If a voucher already exists for this disbursement/issue, it is returned without duplicate posting.
     */
    public function postProductLoanIssue(LoanAccount $loanAccount, LoanDisbursement $disbursement): ?Voucher
    {
        // 1. Idempotency Check
        $existing = Voucher::where('reference_type', 'loan_disbursement')
            ->where('reference_id', $disbursement->id)
            ->first();

        if ($existing) {
            return $existing;
        }

        // 2. Guard: Only Product Loans
        if ($loanAccount->loan_type !== 'product') {
            return null;
        }

        $companyId = $loanAccount->company_id;
        $branchId = $loanAccount->branch_id;

        $application = $loanAccount->application;
        if (!$application || $application->products->count() === 0) {
            throw ValidationException::withMessages(['products' => 'No product items found for this product loan.']);
        }

        // 3. Strict Product Cost Price Validation
        $totalCost = 0.00;
        $totalSellingValue = 0.00;

        foreach ($application->products as $item) {
            $product = $item->product;
            if (!$product || $product->cost_price === null || (float) $product->cost_price <= 0) {
                throw ValidationException::withMessages([
                    'products' => "Product cost price is required before this product can be issued under a Product Loan.",
                ]);
            }

            $qty = (int) $item->quantity;
            $unitPrice = (float) $item->unit_price_snapshot;
            $costPrice = (float) $product->cost_price;

            $totalSellingValue += ($qty * $unitPrice);
            $totalCost += ($qty * $costPrice);
        }

        $totalSellingValue = round($totalSellingValue, 2);
        $totalCost = round($totalCost, 2);

        $financedPrincipal = round((float) $loanAccount->sanctioned_amount, 2);
        $downPaymentAmount = round((float) $loanAccount->down_payment_amount, 2);

        // Ensure Chart of Accounts exist
        $loanReceivableAccount = ChartOfAccount::where('company_id', $companyId)->where('account_code', '1220')->first();
        $downPaymentClearingAccount = ChartOfAccount::where('company_id', $companyId)->where('account_code', '2120')->first();
        $cogsAccount = ChartOfAccount::where('company_id', $companyId)->where('account_code', '5110')->first();
        $salesRevenueAccount = ChartOfAccount::where('company_id', $companyId)->where('account_code', '4310')->first();
        $inventoryAssetAccount = ChartOfAccount::where('company_id', $companyId)->where('account_code', '1310')->first();

        if (!$loanReceivableAccount || !$downPaymentClearingAccount || !$cogsAccount || !$salesRevenueAccount || !$inventoryAssetAccount) {
            $this->seedDefaultChartOfAccounts($companyId);
            $loanReceivableAccount = ChartOfAccount::where('company_id', $companyId)->where('account_code', '1220')->first();
            $downPaymentClearingAccount = ChartOfAccount::where('company_id', $companyId)->where('account_code', '2120')->first();
            $cogsAccount = ChartOfAccount::where('company_id', $companyId)->where('account_code', '5110')->first();
            $salesRevenueAccount = ChartOfAccount::where('company_id', $companyId)->where('account_code', '4310')->first();
            $inventoryAssetAccount = ChartOfAccount::where('company_id', $companyId)->where('account_code', '1310')->first();
        }

        // Adjust for any small floating rounding discrepancies between selling value and principal + down payment
        $expectedSales = round($financedPrincipal + $downPaymentAmount, 2);
        if ($totalSellingValue != $expectedSales && $expectedSales > 0) {
            $totalSellingValue = $expectedSales;
        }

        $entries = [];

        // Debit 1: Product Loans Receivable (Financed Principal)
        $entries[] = [
            'account_id' => $loanReceivableAccount->id,
            'debit' => $financedPrincipal,
            'credit' => 0.00,
            'description' => "Financed Principal - Product Loan #{$loanAccount->loan_number}",
        ];

        // Debit 2: Customer Down Payment Clearing (if down payment > 0)
        if ($downPaymentAmount > 0) {
            $entries[] = [
                'account_id' => $downPaymentClearingAccount->id,
                'debit' => $downPaymentAmount,
                'credit' => 0.00,
                'description' => "Down payment cleared against retail sale - Loan #{$loanAccount->loan_number}",
            ];
        }

        // Debit 3: Cost of Goods Sold (COGS)
        $entries[] = [
            'account_id' => $cogsAccount->id,
            'debit' => $totalCost,
            'credit' => 0.00,
            'description' => "Cost of Goods Sold - Product Loan #{$loanAccount->loan_number}",
        ];

        // Credit 1: Product Loan Retail Sales Revenue
        $entries[] = [
            'account_id' => $salesRevenueAccount->id,
            'debit' => 0.00,
            'credit' => $totalSellingValue,
            'description' => "Retail Sales Revenue - Product Loan #{$loanAccount->loan_number}",
        ];

        // Credit 2: Product Inventory on Hand (Asset Reduction at Cost)
        $entries[] = [
            'account_id' => $inventoryAssetAccount->id,
            'debit' => 0.00,
            'credit' => $totalCost,
            'description' => "Inventory reduction at cost - Product Loan #{$loanAccount->loan_number}",
        ];

        $customerName = $loanAccount->customer ? $loanAccount->customer->full_name : 'Borrower';
        $pDate = $disbursement->disbursement_date
            ? Carbon::parse($disbursement->disbursement_date)->toDateString()
            : now()->toDateString();

        $voucherData = [
            'company_id' => $companyId,
            'branch_id' => $branchId,
            'voucher_type' => 'journal',
            'voucher_date' => $pDate,
            'narration' => "Product fulfillment & sales recognition for Product Loan #{$loanAccount->loan_number} (Customer: {$customerName})",
            'reference_type' => 'loan_disbursement',
            'reference_id' => $disbursement->id,
        ];

        return $this->createVoucher($voucherData, $entries, true);
    }

    /**
     * Resolve or Auto-Provision Chart of Account by Code for Company.
     */
    public function resolveAccount(int $companyId, string $accountCode, array $defaultMeta = []): ChartOfAccount
    {
        $account = ChartOfAccount::where('company_id', $companyId)
            ->where('account_code', $accountCode)
            ->first();

        if ($account) {
            return $account;
        }

        $this->seedDefaultChartOfAccounts($companyId);

        $account = ChartOfAccount::where('company_id', $companyId)
            ->where('account_code', $accountCode)
            ->first();

        if (!$account && !empty($defaultMeta)) {
            $parentId = null;
            if (!empty($defaultMeta['parent_code'])) {
                $parent = ChartOfAccount::where('company_id', $companyId)->where('account_code', $defaultMeta['parent_code'])->first();
                $parentId = $parent?->id;
            }

            $account = ChartOfAccount::create([
                'company_id' => $companyId,
                'account_code' => $accountCode,
                'account_name' => $defaultMeta['account_name'] ?? 'Account ' . $accountCode,
                'account_type' => $defaultMeta['account_type'] ?? 'revenue',
                'account_group' => $defaultMeta['account_group'] ?? 'fee_income',
                'parent_id' => $parentId,
                'is_system' => true,
                'is_active' => true,
            ]);
        }

        return $account;
    }

    /**
     * Post Automatic General Ledger Voucher for Loan Foreclosure (Early Payoff).
     */
    public function postLoanForeclosure(
        LoanRepayment $repayment,
        LoanAccount $loanAccount,
        float $foreclosureFee = 0.00,
        float $accruedInterest = 0.00,
        float $principalPaid = 0.00,
        float $feePaid = 0.00,
        float $penaltyPaid = 0.00
    ): ?Voucher {
        $existing = Voucher::where('reference_type', 'loan_foreclosure')
            ->where('reference_id', $repayment->id)
            ->first();

        if ($existing) {
            return $existing;
        }

        $totalAmount = round((float) $repayment->amount, 2);
        if ($totalAmount <= 0) {
            return null;
        }

        $companyId = $loanAccount->company_id;
        $branchId = $loanAccount->branch_id;

        $principalCode = ($loanAccount->loan_type === 'product') ? '1220' : '1210';
        $interestCode = ($loanAccount->loan_type === 'product') ? '4120' : '4110';

        $principalAccount = $this->resolveAccount($companyId, $principalCode);
        $interestAccount = $this->resolveAccount($companyId, $interestCode);
        $feeAccount = $this->resolveAccount($companyId, '4210');
        $penaltyAccount = $this->resolveAccount($companyId, '4230');
        $foreclosureFeeAccount = $this->resolveAccount($companyId, '4240', [
            'account_name' => 'Foreclosure & Prepayment Fee Income',
            'account_type' => 'revenue',
            'account_group' => 'fee_income',
            'parent_code' => '4200',
        ]);

        $debitAccount = $this->resolvePaymentMethodAccount($companyId, $branchId, $repayment->payment_method);

        $entries = [];

        // Debit: Cash / Bank
        $entries[] = [
            'account_id' => $debitAccount->id,
            'debit' => $totalAmount,
            'credit' => 0.00,
            'description' => "Early Foreclosure Payoff Receipt #{$repayment->receipt_number} via " . ucfirst(str_replace('_', ' ', $repayment->payment_method)),
        ];

        // Credits:
        if ($principalPaid > 0) {
            $entries[] = [
                'account_id' => $principalAccount->id,
                'debit' => 0.00,
                'credit' => $principalPaid,
                'description' => "Principal Cleared on Foreclosure - Loan #{$loanAccount->loan_number}",
            ];
        }

        if ($accruedInterest > 0) {
            $entries[] = [
                'account_id' => $interestAccount->id,
                'debit' => 0.00,
                'credit' => $accruedInterest,
                'description' => "Earned Accrued Interest on Foreclosure - Loan #{$loanAccount->loan_number}",
            ];
        }

        if ($feePaid > 0) {
            $entries[] = [
                'account_id' => $feeAccount->id,
                'debit' => 0.00,
                'credit' => $feePaid,
                'description' => "Fee Recovery on Foreclosure - Loan #{$loanAccount->loan_number}",
            ];
        }

        if ($penaltyPaid > 0) {
            $entries[] = [
                'account_id' => $penaltyAccount->id,
                'debit' => 0.00,
                'credit' => $penaltyPaid,
                'description' => "Penalty Collected on Foreclosure - Loan #{$loanAccount->loan_number}",
            ];
        }

        if ($foreclosureFee > 0) {
            $entries[] = [
                'account_id' => $foreclosureFeeAccount->id,
                'debit' => 0.00,
                'credit' => $foreclosureFee,
                'description' => "Foreclosure & Prepayment Fee - Loan #{$loanAccount->loan_number}",
            ];
        }

        $customerName = $loanAccount->customer ? $loanAccount->customer->full_name : 'Borrower';
        $pDate = $repayment->payment_date ?: now()->toDateString();

        $voucherData = [
            'company_id' => $companyId,
            'branch_id' => $branchId,
            'voucher_type' => 'receipt',
            'voucher_date' => $pDate,
            'narration' => "Early Foreclosure Payoff Receipt for Loan #{$loanAccount->loan_number} (Customer: {$customerName})",
            'reference_type' => 'loan_foreclosure',
            'reference_id' => $repayment->id,
        ];

        return $this->createVoucher($voucherData, $entries, true);
    }

    /**
     * Post Automatic General Ledger Voucher for One-Time Settlement (OTS / Compromise).
     */
    public function postLoanSettlement(
        LoanRepayment $repayment,
        LoanAccount $loanAccount,
        \App\Models\LoanSettlementRequest $request,
        array $allocation
    ): ?Voucher {
        $existing = Voucher::where('reference_type', 'loan_settlement')
            ->where('reference_id', $request->id)
            ->first();

        if ($existing) {
            return $existing;
        }

        $totalCash = round((float) $repayment->amount, 2);
        $companyId = $loanAccount->company_id;
        $branchId = $loanAccount->branch_id;

        $principalCode = ($loanAccount->loan_type === 'product') ? '1220' : '1210';
        $interestCode = ($loanAccount->loan_type === 'product') ? '4120' : '4110';

        $principalAccount = $this->resolveAccount($companyId, $principalCode);
        $interestAccount = $this->resolveAccount($companyId, $interestCode);
        $feeAccount = $this->resolveAccount($companyId, '4210');
        $penaltyAccount = $this->resolveAccount($companyId, '4230');
        $loanLossAccount = $this->resolveAccount($companyId, '5120');

        $debitAccount = $this->resolvePaymentMethodAccount($companyId, $branchId, $repayment->payment_method);

        $principalRecovered = round((float) ($allocation['principal_recovered'] ?? 0.00), 2);
        $interestRecovered = round((float) ($allocation['interest_recovered'] ?? 0.00), 2);
        $feeRecovered = round((float) ($allocation['fee_recovered'] ?? 0.00), 2);
        $penaltyRecovered = round((float) ($allocation['penalty_recovered'] ?? 0.00), 2);
        $principalLoss = round((float) ($allocation['principal_loss'] ?? 0.00), 2);
        $fullPrincipalCleared = round((float) ($request->principal_outstanding > 0 ? $request->principal_outstanding : ($principalRecovered + $principalLoss ?: $loanAccount->principal_outstanding)), 2);

        $entries = [];

        // Debit 1: Cash/Bank Vault for actual cash collected
        if ($totalCash > 0) {
            $entries[] = [
                'account_id' => $debitAccount->id,
                'debit' => $totalCash,
                'credit' => 0.00,
                'description' => "Compromise Settlement (OTS) Receipt #{$repayment->receipt_number} via " . ucfirst(str_replace('_', ' ', $repayment->payment_method)),
            ];
        }

        // Debit 2: Loan Loss Provisioning & Write-Offs for Principal Haircut
        if ($principalLoss > 0) {
            $entries[] = [
                'account_id' => $loanLossAccount->id,
                'debit' => $principalLoss,
                'credit' => 0.00,
                'description' => "Principal Concession / Haircut Loss on OTS Settlement - Loan #{$loanAccount->loan_number}",
            ];
        }

        // Credit 1: 100% Full Principal Cleared
        if ($fullPrincipalCleared > 0) {
            $entries[] = [
                'account_id' => $principalAccount->id,
                'debit' => 0.00,
                'credit' => $fullPrincipalCleared,
                'description' => "Full Principal Cleared via Approved Settlement - Loan #{$loanAccount->loan_number}",
            ];
        }

        // Credit 2: Actually Recovered Interest
        if ($interestRecovered > 0) {
            $entries[] = [
                'account_id' => $interestAccount->id,
                'debit' => 0.00,
                'credit' => $interestRecovered,
                'description' => "Actual Interest Recovered on Settlement - Loan #{$loanAccount->loan_number}",
            ];
        }

        // Credit 3: Actually Recovered Fee
        if ($feeRecovered > 0) {
            $entries[] = [
                'account_id' => $feeAccount->id,
                'debit' => 0.00,
                'credit' => $feeRecovered,
                'description' => "Actual Fees Recovered on Settlement - Loan #{$loanAccount->loan_number}",
            ];
        }

        // Credit 4: Actually Recovered Penalty
        if ($penaltyRecovered > 0) {
            $entries[] = [
                'account_id' => $penaltyAccount->id,
                'debit' => 0.00,
                'credit' => $penaltyRecovered,
                'description' => "Actual Penalty Recovered on Settlement - Loan #{$loanAccount->loan_number}",
            ];
        }

        $customerName = $loanAccount->customer ? $loanAccount->customer->full_name : 'Borrower';
        $pDate = $repayment->payment_date ?: now()->toDateString();

        $voucherData = [
            'company_id' => $companyId,
            'branch_id' => $branchId,
            'voucher_type' => 'receipt',
            'voucher_date' => $pDate,
            'narration' => "One-Time Settlement (OTS) Discharge Voucher for Loan #{$loanAccount->loan_number} (Customer: {$customerName})",
            'reference_type' => 'loan_settlement',
            'reference_id' => $request->id,
        ];

        return $this->createVoucher($voucherData, $entries, true);
    }

    /**
     * Post Automatic General Ledger Voucher for Bad Debt Loan Write-Off.
     */
    public function postLoanWriteOff(
        LoanAccount $loanAccount,
        \App\Models\LoanSettlementRequest $request
    ): ?Voucher {
        $existing = Voucher::where('reference_type', 'loan_write_off')
            ->where('reference_id', $request->id)
            ->first();

        if ($existing) {
            return $existing;
        }

        $companyId = $loanAccount->company_id;
        $branchId = $loanAccount->branch_id;
        $uncollectiblePrincipal = round((float) ($request->principal_outstanding > 0 ? $request->principal_outstanding : $loanAccount->principal_outstanding), 2);

        if ($uncollectiblePrincipal <= 0) {
            return null;
        }

        $principalCode = ($loanAccount->loan_type === 'product') ? '1220' : '1210';
        $principalAccount = $this->resolveAccount($companyId, $principalCode);
        $loanLossAccount = $this->resolveAccount($companyId, '5120');

        $entries = [
            [
                'account_id' => $loanLossAccount->id,
                'debit' => $uncollectiblePrincipal,
                'credit' => 0.00,
                'description' => "Bad Debt Write-Off Loss - Loan #{$loanAccount->loan_number}",
            ],
            [
                'account_id' => $principalAccount->id,
                'debit' => 0.00,
                'credit' => $uncollectiblePrincipal,
                'description' => "Uncollectible Principal Written Off - Loan #{$loanAccount->loan_number}",
            ],
        ];

        $customerName = $loanAccount->customer ? $loanAccount->customer->full_name : 'Borrower';

        $voucherData = [
            'company_id' => $companyId,
            'branch_id' => $branchId,
            'voucher_type' => 'journal',
            'voucher_date' => now()->toDateString(),
            'narration' => "Bad Debt Principal Write-Off for Defaulted Loan #{$loanAccount->loan_number} (Customer: {$customerName})",
            'reference_type' => 'loan_write_off',
            'reference_id' => $request->id,
        ];

        return $this->createVoucher($voucherData, $entries, true);
    }
}
