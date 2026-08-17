<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Daily synchronization of loan overdue statuses at 00:05 IST
Schedule::command('loans:sync-overdue-status')
    ->dailyAt('00:05')
    ->timezone('Asia/Kolkata');

// Daily automated late penalty accrual at 00:10 IST
Schedule::command('loans:apply-penalties')
    ->dailyAt('00:10')
    ->timezone('Asia/Kolkata');
