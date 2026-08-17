<?php

namespace App\Console\Commands;

use App\Services\PenaltyService;
use Carbon\Carbon;
use Illuminate\Console\Command;

class ApplyDailyPenaltiesCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'loans:apply-penalties {--company= : Specific Company ID} {--date= : Specific As-Of Date (YYYY-MM-DD)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Apply automated late penalty charges to overdue installments past grace period';

    /**
     * Execute the console command.
     */
    public function handle(PenaltyService $penaltyService): int
    {
        $companyId = $this->option('company') ? (int) $this->option('company') : null;
        $dateStr = $this->option('date') ?: Carbon::now(PenaltyService::TIMEZONE)->toDateString();
        $asOfDate = Carbon::createFromFormat('Y-m-d', substr($dateStr, 0, 10), PenaltyService::TIMEZONE)->startOfDay();

        $this->info("Starting Daily Late Penalty Accrual as of [{$asOfDate->toDateString()}] (Timezone: Asia/Kolkata)...");

        $result = $penaltyService->applyDailyPenalties($companyId, $asOfDate);

        $this->info("Processed Installments: {$result['processed_installments']}");
        $this->info("Penalized Installments: {$result['penalized_installments']}");
        $this->info("Skipped (Already Charged Today): {$result['skipped_already_charged']}");
        $this->info("Total New Penalty Applied: ₹" . number_format($result['total_penalty_applied'], 2));

        $this->info("Daily Late Penalty Accrual Completed Successfully.");

        return Command::SUCCESS;
    }
}
