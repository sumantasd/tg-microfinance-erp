<?php

namespace Tests\Unit;

use App\Services\LoanAccountService;
use Carbon\Carbon;
use PHPUnit\Framework\TestCase;

class EmiRoundingTest extends TestCase
{
    protected LoanAccountService $loanAccountService;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Instantiate LoanAccountService mock/concrete dependencies for unit testing
        $accountRepo = $this->createMock(\App\Repositories\LoanAccountRepositoryInterface::class);
        $inventoryRepo = $this->createMock(\App\Repositories\InventoryRepositoryInterface::class);
        $activityLog = $this->createMock(\App\Services\ActivityLogService::class);
        $accountingService = $this->createMock(\App\Services\AccountingService::class);

        $this->loanAccountService = new LoanAccountService(
            $accountRepo,
            $inventoryRepo,
            $activityLog,
            $accountingService
        );
    }

    /** @test */
    public function test_exact_whole_number_emi_remains_unchanged()
    {
        // Principal 7500, Flat 0% interest, 6 months => raw EMI = 1250.00
        $schedule = $this->loanAccountService->calculateRepaymentSchedule(
            7500.00,
            6,
            'monthly',
            'flat',
            0.00,
            Carbon::parse('2026-01-01')
        );

        $installments = $schedule['installments'];
        $this->assertCount(6, $installments);

        foreach ($installments as $inst) {
            $this->assertEquals(1250.00, $inst['installment_amount']);
            $this->assertEquals(0, fmod($inst['installment_amount'], 1));
        }

        $this->assertEquals(7500.00, array_sum(array_column($installments, 'installment_amount')));
    }

    /** @test */
    public function test_regular_emi_rounds_up_ceiling_and_final_emi_absorbs_difference_example_3()
    {
        // Prompt Example 3: Total Payable 7504.00, 6 months => raw EMI = 1250.6667 => Regular EMI = 1251
        $schedule = $this->loanAccountService->calculateRepaymentSchedule(
            7000.00,
            6,
            'monthly',
            'flat',
            14.40, // 7000 * 0.144 * (6/12) = 504.00 total interest => total payable = 7504.00
            Carbon::parse('2026-01-01')
        );

        $installments = $schedule['installments'];
        $this->assertCount(6, $installments);

        // Raw EMI = 7504 / 6 = 1250.6667 => Regular EMI = 1251
        for ($i = 0; $i < 5; $i++) {
            $this->assertEquals(1251.00, $installments[$i]['installment_amount'], "Installment #" . ($i + 1) . " must be rounded UP to 1251");
            $this->assertEquals(0, fmod($installments[$i]['installment_amount'], 1));
        }

        // Final EMI = 7504 - (5 * 1251) = 7504 - 6255 = 1249
        $finalInst = $installments[5];
        $this->assertEquals(1249.00, $finalInst['installment_amount'], "Final EMI must absorb rounding difference (1249)");
        $this->assertEquals(0, fmod($finalInst['installment_amount'], 1));

        $sumInstallments = array_sum(array_column($installments, 'installment_amount'));
        $this->assertEquals(7504.00, $sumInstallments);
        $this->assertEquals($schedule['total_repayment'], $sumInstallments);
    }

    /** @test */
    public function test_prompt_example_4_total_payable_60005_over_6_months()
    {
        // Prompt Example 4: Total Payable 60005, 6 months => raw EMI = 10000.8333 => Regular EMI = 10001
        // Installments 1..5 = 10001, Installment 6 = 10000. Sum = 60005
        $schedule = $this->loanAccountService->calculateRepaymentSchedule(
            60005.00,
            6,
            'monthly',
            'flat',
            0.00,
            Carbon::parse('2026-01-01')
        );

        $installments = $schedule['installments'];
        $this->assertCount(6, $installments);

        for ($i = 0; $i < 5; $i++) {
            $this->assertEquals(10001.00, $installments[$i]['installment_amount']);
            $this->assertEquals(0, fmod($installments[$i]['installment_amount'], 1));
        }

        $this->assertEquals(10000.00, $installments[5]['installment_amount']);
        $this->assertEquals(0, fmod($installments[5]['installment_amount'], 1));

        $this->assertEquals(60005.00, array_sum(array_column($installments, 'installment_amount')));
    }

    /** @test */
    public function test_ceiling_rounding_for_various_decimal_paisa_amounts()
    {
        // Test 1250.01 -> 1251
        $schedule1 = $this->loanAccountService->calculateRepaymentSchedule(
            7500.06, 6, 'monthly', 'flat', 0.00, Carbon::parse('2026-01-01')
        );
        $this->assertEquals(1251.00, $schedule1['installments'][0]['installment_amount']);

        // Test 1250.10 -> 1251
        $schedule2 = $this->loanAccountService->calculateRepaymentSchedule(
            7500.60, 6, 'monthly', 'flat', 0.00, Carbon::parse('2026-01-01')
        );
        $this->assertEquals(1251.00, $schedule2['installments'][0]['installment_amount']);

        // Test 1250.50 -> 1251
        $schedule3 = $this->loanAccountService->calculateRepaymentSchedule(
            7503.00, 6, 'monthly', 'flat', 0.00, Carbon::parse('2026-01-01')
        );
        $this->assertEquals(1251.00, $schedule3['installments'][0]['installment_amount']);

        // Test 2499.35 -> 2500
        $schedule4 = $this->loanAccountService->calculateRepaymentSchedule(
            14996.10, 6, 'monthly', 'flat', 0.00, Carbon::parse('2026-01-01')
        );
        $this->assertEquals(2500.00, $schedule4['installments'][0]['installment_amount']);
    }

    /** @test */
    public function test_weekly_and_bi_weekly_frequencies_use_whole_rupees()
    {
        // Weekly: 3 months = 12 periods
        $weeklySchedule = $this->loanAccountService->calculateRepaymentSchedule(
            10000.00, 3, 'weekly', 'flat', 12.00, Carbon::parse('2026-01-01')
        );
        $this->assertCount(12, $weeklySchedule['installments']);
        foreach ($weeklySchedule['installments'] as $inst) {
            $this->assertEquals(0, fmod($inst['installment_amount'], 1), "Weekly installment must contain no paisa");
        }

        // Bi-weekly: 6 months = 12 periods
        $biWeeklySchedule = $this->loanAccountService->calculateRepaymentSchedule(
            25000.00, 6, 'bi_weekly', 'reducing', 18.00, Carbon::parse('2026-01-01')
        );
        $this->assertCount(12, $biWeeklySchedule['installments']);
        foreach ($biWeeklySchedule['installments'] as $inst) {
            $this->assertEquals(0, fmod($inst['installment_amount'], 1), "Bi-weekly installment must contain no paisa");
        }
    }

    /** @test */
    public function test_reducing_balance_interest_uses_whole_rupee_emi_and_reconciles()
    {
        $schedule = $this->loanAccountService->calculateRepaymentSchedule(
            50000.00, 12, 'monthly', 'reducing', 18.00, Carbon::parse('2026-01-01')
        );

        $installments = $schedule['installments'];
        $this->assertCount(12, $installments);

        foreach ($installments as $inst) {
            $this->assertEquals(0, fmod($inst['installment_amount'], 1), "All Reducing Balance installments must be whole rupees");
        }

        $sumInstallments = array_sum(array_column($installments, 'installment_amount'));
        $this->assertEquals($schedule['total_repayment'], $sumInstallments, "Total repayment must reconcile with sum of installments");
    }
}
