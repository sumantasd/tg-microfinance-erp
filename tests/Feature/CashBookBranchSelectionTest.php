<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\CashBook;
use App\Models\CashBookEntry;
use App\Models\Company;
use App\Models\User;
use App\Services\CashBookService;
use Database\Seeders\RbacSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CashBookBranchSelectionTest extends TestCase
{
    use RefreshDatabase;

    protected Company $company1;
    protected Company $company2;
    protected Branch $branch1;
    protected Branch $branch2;
    protected Branch $company2Branch;
    protected User $superAdmin;
    protected User $branchManager1;
    protected CashBookService $cashBookService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RbacSeeder::class);

        $this->cashBookService = app(CashBookService::class);

        $this->company1 = Company::create([
            'name' => 'Grihalaxmi Finance Bihar',
            'code' => 'GLF-BIHAR',
            'email' => 'bihar@grihalaxmi.com',
            'phone' => '9876543210',
            'address' => 'Patna',
            'is_active' => true,
        ]);

        $this->company2 = Company::create([
            'name' => 'Grihalaxmi Finance Bengal',
            'code' => 'GLF-BENGAL',
            'email' => 'bengal@grihalaxmi.com',
            'phone' => '9876543220',
            'address' => 'Kolkata',
            'is_active' => true,
        ]);

        $this->branch1 = Branch::create([
            'company_id' => $this->company1->id,
            'name' => 'Patna Main Branch',
            'code' => 'BR-PATNA',
            'phone' => '9876543211',
            'address' => 'Patna Main Road',
            'city' => 'Patna',
            'state' => 'Bihar',
            'pincode' => '800001',
            'is_active' => true,
        ]);

        $this->branch2 = Branch::create([
            'company_id' => $this->company1->id,
            'name' => 'Gaya Branch',
            'code' => 'BR-GAYA',
            'phone' => '9876543212',
            'address' => 'Gaya Station Road',
            'city' => 'Gaya',
            'state' => 'Bihar',
            'pincode' => '823001',
            'is_active' => true,
        ]);

        $this->company2Branch = Branch::create([
            'company_id' => $this->company2->id,
            'name' => 'Kolkata Central Branch',
            'code' => 'BR-KOLKATA',
            'phone' => '9876543221',
            'address' => 'Park Street Kolkata',
            'city' => 'Kolkata',
            'state' => 'West Bengal',
            'pincode' => '700001',
            'is_active' => true,
        ]);

        $this->superAdmin = User::create([
            'company_id' => $this->company1->id,
            'branch_id' => null,
            'name' => 'Super Admin User',
            'email' => 'superadmin.cb@grihalaxmi.com',
            'password' => bcrypt('Password123!'),
            'status' => 'active',
        ]);
        $this->superAdmin->assignRole('Super Admin');

        $this->branchManager1 = User::create([
            'company_id' => $this->company1->id,
            'branch_id' => $this->branch1->id,
            'name' => 'Patna Branch Manager',
            'email' => 'bm.patna@grihalaxmi.com',
            'password' => bcrypt('Password123!'),
            'status' => 'active',
        ]);
        $this->branchManager1->assignRole('Branch Manager');
    }

    /**
     * Step 1 Test: Super Admin visiting /admin/cash-book sees all active branches of their company
     */
    public function test_super_admin_sees_all_active_branches_on_selection_page(): void
    {
        $response = $this->actingAs($this->superAdmin)->get(route('admin.cash-book.index'));

        $response->assertStatus(200);
        $response->assertSee('Cashbook — Select Branch');
        $response->assertSee('Patna Main Branch');
        $response->assertSee('BR-PATNA');
        $response->assertSee('Gaya Branch');
        $response->assertSee('BR-GAYA');
        $response->assertSee('View Cashbook');
    }

    /**
     * Step 1 Test: Branch Manager restricted to Branch 1 only sees Branch 1 on selection page
     */
    public function test_branch_manager_sees_only_assigned_branch_on_selection_page(): void
    {
        $response = $this->actingAs($this->branchManager1)->get(route('admin.cash-book.index'));

        $response->assertStatus(200);
        $response->assertSee('Patna Main Branch');
        $response->assertSee('BR-PATNA');
        $response->assertDontSee('BR-GAYA');
        $response->assertDontSee('BR-KOLKATA');
    }

    /**
     * Step 1 Test: Branch search filter on selection page
     */
    public function test_branch_search_filters_branches_correctly(): void
    {
        $response = $this->actingAs($this->superAdmin)->get(route('admin.cash-book.index', ['search' => 'Gaya']));

        $response->assertStatus(200);
        $response->assertSee('Gaya Branch');
        $response->assertSee('BR-GAYA');
        $response->assertDontSee('BR-PATNA');
    }

    /**
     * Step 2 & 3 Test: Opening a branch cashbook displays date-wise overview with branch context
     */
    public function test_open_branch_cashbook_displays_date_wise_overview(): void
    {
        $response = $this->actingAs($this->superAdmin)->get(route('admin.cash-book.branch', $this->branch1->id));

        $response->assertStatus(200);
        $response->assertSee('CASHBOOK OVERVIEW');
        $response->assertSee('PATNA MAIN BRANCH');
        $response->assertSee('BR-PATNA');
        $response->assertSee('Back to Branch Selection');
        $response->assertSee('Historical Cashbook Registers');
    }

    /**
     * Step 3 Test: Date-wise overview lists historical records and links to detailed register
     */
    public function test_date_wise_overview_lists_historical_records(): void
    {
        $pastDate1 = '2026-09-25';
        $pastDate2 = '2026-09-26';

        $cb1 = CashBook::create([
            'company_id' => $this->company1->id,
            'branch_id' => $this->branch1->id,
            'date' => $pastDate1,
            'opening_balance' => 10000.00,
            'closing_cash' => 12000.00,
            'status' => 'closed',
            'created_by' => $this->superAdmin->id,
        ]);

        $cb2 = CashBook::create([
            'company_id' => $this->company1->id,
            'branch_id' => $this->branch1->id,
            'date' => $pastDate2,
            'opening_balance' => 12000.00,
            'closing_cash' => 15000.00,
            'status' => 'closed',
            'created_by' => $this->superAdmin->id,
        ]);

        $response = $this->actingAs($this->superAdmin)->get(route('admin.cash-book.branch', $this->branch1->id));

        $response->assertStatus(200);
        $response->assertSee('25/09/2026');
        $response->assertSee('26/09/2026');
        $response->assertSee(route('admin.cash-book.show', $cb1->id));
        $response->assertSee(route('admin.cash-book.show', $cb2->id));
    }

    /**
     * Step 3 Test: Overview supports date filtering
     */
    public function test_branch_overview_date_filtering(): void
    {
        $targetDate = '2026-09-20';
        $otherDate = '2026-09-21';

        CashBook::create([
            'company_id' => $this->company1->id,
            'branch_id' => $this->branch1->id,
            'date' => $targetDate,
            'opening_balance' => 5000.00,
            'status' => 'closed',
            'created_by' => $this->superAdmin->id,
        ]);

        CashBook::create([
            'company_id' => $this->company1->id,
            'branch_id' => $this->branch1->id,
            'date' => $otherDate,
            'opening_balance' => 8000.00,
            'status' => 'closed',
            'created_by' => $this->superAdmin->id,
        ]);

        $response = $this->actingAs($this->superAdmin)->get(route('admin.cash-book.branch', [
            'branchId' => $this->branch1->id,
            'date' => $targetDate,
        ]));

        $response->assertStatus(200);
        $response->assertSee('20/09/2026');
        $response->assertDontSee('21/09/2026');
    }

    /**
     * Step 4 Test: Detailed Cashbook ledger view has proper navigation back to overview and branch selection
     */
    public function test_detailed_cashbook_has_proper_back_navigation(): void
    {
        $cashBook = $this->cashBookService->getOrCreateCashBook($this->company1->id, $this->branch1->id, date('Y-m-d'), $this->superAdmin->id);

        $response = $this->actingAs($this->superAdmin)->get(route('admin.cash-book.show', $cashBook->id));

        $response->assertStatus(200);
        $response->assertSee('DAILY CASH BOOK REGISTER');
        $response->assertSee(route('admin.cash-book.branch', $this->branch1->id));
        $response->assertSee(route('admin.cash-book.index'));
        $response->assertSee('Back to Overview');
        $response->assertSee('Branch Selection');
    }

    /**
     * RBAC Test: Server-side authorization blocks unauthorized branch access attempt
     */
    public function test_unauthorized_branch_access_returns_403_forbidden(): void
    {
        // Branch Manager 1 (Patna) attempts to access Gaya branch overview via direct URL
        $response = $this->actingAs($this->branchManager1)->get(route('admin.cash-book.branch', $this->branch2->id));

        $response->assertStatus(403);
    }

    /**
     * RBAC Test: Server-side authorization blocks unauthorized detail register access attempt
     */
    public function test_unauthorized_detail_register_access_returns_403_forbidden(): void
    {
        $gayaCashBook = $this->cashBookService->getOrCreateCashBook($this->company1->id, $this->branch2->id, date('Y-m-d'), $this->superAdmin->id);

        // Branch Manager 1 (Patna) attempts to access Gaya branch register detail
        $response = $this->actingAs($this->branchManager1)->get(route('admin.cash-book.show', $gayaCashBook->id));

        $response->assertStatus(403);
    }

    /**
     * RBAC Test: Company isolation blocks access to another company branch cashbook
     */
    public function test_company_isolation_returns_403_for_other_company_branch(): void
    {
        $response = $this->actingAs($this->superAdmin)->get(route('admin.cash-book.branch', $this->company2Branch->id));

        $response->assertStatus(403);
    }

    /**
     * Accounting Test: Branch cash balance calculation matches CashBookService closing cash
     */
    public function test_branch_cash_balance_matches_accounting_service(): void
    {
        $yesterday = date('Y-m-d', strtotime('-1 day'));
        $today = date('Y-m-d');

        // Create previous day cashbook ending with 12,500 physical cash
        $prevCb = CashBook::create([
            'company_id' => $this->company1->id,
            'branch_id' => $this->branch1->id,
            'date' => $yesterday,
            'opening_balance' => 12500.00,
            'status' => 'closed',
            'created_by' => $this->superAdmin->id,
        ]);

        CashBookEntry::create([
            'cash_book_id' => $prevCb->id,
            'entry_type' => 'received',
            'category_code' => 'cash_opening_balance',
            'particulars' => 'CASH OPENING BALANCE',
            'entry_date' => $yesterday,
            'cash_amount' => 12500.00,
        ]);

        $this->cashBookService->recalculateTotals($prevCb);

        // Today's cashbook initialization
        $cashBook = $this->cashBookService->getOrCreateCashBook($this->company1->id, $this->branch1->id, $today, $this->superAdmin->id);

        CashBookEntry::where('cash_book_id', $cashBook->id)
            ->where('category_code', 'weekly_collection')
            ->update(['cash_amount' => 7500.00]);

        $this->cashBookService->recalculateTotals($cashBook);
        $cashBook->refresh();

        $this->assertEquals(20000.00, (float)$cashBook->closing_cash);

        $response = $this->actingAs($this->superAdmin)->get(route('admin.cash-book.index'));
        $response->assertStatus(200);
        $response->assertSee('₹20,000.00');
    }
}
