<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\CashBook;
use App\Models\CashBookEntry;
use App\Models\Company;
use App\Models\User;
use App\Services\CashBookService;
use Database\Seeders\AdminUserSeeder;
use Database\Seeders\RbacSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class CashBookModuleTest extends TestCase
{
    use RefreshDatabase;

    protected Company $company;
    protected Branch $branch;
    protected User $admin;
    protected CashBookService $cashBookService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed([
            RbacSeeder::class,
            AdminUserSeeder::class,
        ]);

        $this->cashBookService = app(CashBookService::class);

        $this->company = Company::create([
            'name' => 'Grihalaxmi Finance Test',
            'code' => 'GLFTEST',
            'email' => 'test@grihalaxmifinance.com',
            'phone' => '9876543210',
            'address' => 'Patna Main Road',
            'is_active' => true,
        ]);

        $this->branch = Branch::create([
            'company_id' => $this->company->id,
            'name' => 'Main Branch Patna',
            'code' => 'BR-PATNA',
            'phone' => '9876543211',
            'address' => 'Patna',
            'city' => 'Patna',
            'state' => 'Bihar',
            'pincode' => '800001',
            'is_active' => true,
        ]);

        $this->admin = User::where('email', 'admin@grihalaxmifinance.com')->first() ?: User::first();
        if ($this->admin) {
            $this->admin->update([
                'company_id' => $this->company->id,
                'branch_id' => $this->branch->id,
                'status' => 'active',
            ]);
            $this->admin->syncRoles(['Super Admin']);
        }
    }

    public function test_cash_book_permissions_registered(): void
    {
        $this->assertTrue(Role::where('name', 'Super Admin')->exists());
        $this->assertDatabaseHas('permissions', ['name' => 'cashbook.view']);
        $this->assertDatabaseHas('permissions', ['name' => 'cashbook.close']);
    }

    public function test_get_or_create_cash_book_initializes_register_correctly(): void
    {
        $date = '2026-08-31';

        $cashBook = $this->cashBookService->getOrCreateCashBook(
            $this->company->id,
            $this->branch->id,
            $date,
            $this->admin->id
        );

        $this->assertNotNull($cashBook);
        $this->assertEquals('2026-08-31', $cashBook->date->format('Y-m-d'));
        $this->assertEquals('open', $cashBook->status);
        $this->assertCount(8, $cashBook->receivedEntries);
        $this->assertCount(7, $cashBook->paymentEntries);
    }

    public function test_add_and_update_particular_entries_recalculates_closing_cash(): void
    {
        $cashBook = $this->cashBookService->getOrCreateCashBook(
            $this->company->id,
            $this->branch->id,
            '2026-08-31',
            $this->admin->id
        );

        // Set opening balance = 90
        $cashBook->update(['opening_balance' => 90.00]);

        // Add Weekly Collection = 11110 (Received)
        $this->cashBookService->addOrUpdateEntry($cashBook, [
            'entry_type' => 'received',
            'category_code' => 'weekly_collection',
            'particulars' => 'WEEKLY COLLECTION',
            'cash_amount' => 11110.00,
        ], $this->admin->id);

        // Add Bank Deposit = 9650 (Payment)
        $this->cashBookService->addOrUpdateEntry($cashBook, [
            'entry_type' => 'payment',
            'category_code' => 'deposit_to_bank',
            'particulars' => 'DEPOSIT TO BANK',
            'cash_amount' => 9650.00,
        ], $this->admin->id);

        // Add Management Expense = 70 (Payment)
        $this->cashBookService->addOrUpdateEntry($cashBook, [
            'entry_type' => 'payment',
            'category_code' => 'management_expense',
            'particulars' => 'MANAGEMENT EXPENSE',
            'cash_amount' => 70.00,
        ], $this->admin->id);

        // Add Product Transaction = 1390 (Payment)
        $this->cashBookService->addOrUpdateEntry($cashBook, [
            'entry_type' => 'payment',
            'category_code' => 'gl_steel_furniture',
            'particulars' => 'GL STEEL FURNITURE BY CUSTOMER',
            'cash_amount' => 1390.00,
        ], $this->admin->id);

        $cashBook->refresh();

        // Formula: Opening (90) + Weekly Collection (11110) = 11200 Total Received. Total Payment = 11110. Closing Cash = 11200 - 11110 = 90.
        $this->assertEquals(11200.00, (float)$cashBook->total_cash_received);
        $this->assertEquals(11110.00, (float)$cashBook->total_cash_payment);
        $this->assertEquals(90.00, (float)$cashBook->closing_cash);
    }

    public function test_cash_denomination_reconciliation(): void
    {
        $cashBook = $this->cashBookService->getOrCreateCashBook(
            $this->company->id,
            $this->branch->id,
            '2026-08-31',
            $this->admin->id
        );

        $cashBook->update(['opening_balance' => 90.00]);
        $this->cashBookService->recalculateTotals($cashBook);
        $cashBook->refresh();

        $this->assertEquals(90.00, (float)$cashBook->physical_cash);
        $this->assertEquals(0.00, (float)$cashBook->cash_difference);
        $this->assertEquals('balanced', $cashBook->reconciled_status);
    }

    public function test_close_and_reopen_cash_book_register(): void
    {
        $cashBook = $this->cashBookService->getOrCreateCashBook(
            $this->company->id,
            $this->branch->id,
            '2026-08-31',
            $this->admin->id
        );

        $this->cashBookService->closeCashBook($cashBook, $this->admin->id, 'Daily closing completed');
        $cashBook->refresh();

        $this->assertTrue($cashBook->isClosed());
        $this->assertEquals('closed', $cashBook->status);

        // Reopen
        $this->cashBookService->reopenCashBook($cashBook, $this->admin->id, 'Need to add late collection');
        $cashBook->refresh();

        $this->assertTrue($cashBook->isOpen());
        $this->assertEquals('open', $cashBook->status);
    }

    public function test_cash_book_routes_access(): void
    {
        $cashBook = $this->cashBookService->getOrCreateCashBook(
            $this->company->id,
            $this->branch->id,
            '2026-08-31',
            $this->admin->id
        );

        $response = $this->actingAs($this->admin)->get(route('admin.cash-book.index'));
        $response->assertStatus(200);

        $showResponse = $this->actingAs($this->admin)->get(route('admin.cash-book.show', $cashBook->id));
        $showResponse->assertStatus(200);
        $showResponse->assertSee('DAILY CASH BOOK REGISTER');

        $printResponse = $this->actingAs($this->admin)->get(route('admin.cash-book.print', $cashBook->id));
        $printResponse->assertStatus(200);
        $printResponse->assertSee('DAILY CASH BOOK REGISTER');
    }
}
