<?php

namespace App\Console\Commands;

use App\Services\OverdueService;
use Illuminate\Console\Command;

class SyncLoanOverdueStatusCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'loans:sync-overdue-status {--company= : Company ID to scope the sync} {--date= : As-of date for evaluation (YYYY-MM-DD)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Synchronize and update database status to overdue for past-due loan installments';

    /**
     * Execute the console command.
     */
    public function handle(OverdueService $overdueService): int
    {
        $companyId = $this->option('company') ? (int) $this->option('company') : null;
        $date = $this->option('date') ?: null;

        $this->info('Starting loan overdue status synchronization...');

        $updatedCount = $overdueService->syncOverdueDatabaseStatuses($companyId, $date);

        $this->info("Successfully synchronized {$updatedCount} past-due loan installments to 'overdue' status.");

        return Command::SUCCESS;
    }
}
