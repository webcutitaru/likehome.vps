<?php

use App\Console\Commands\ExpirePendingBookingsCommand;
use App\Console\Commands\SendCheckinRemindersCommand;
use App\Console\Commands\SendPaymentRemindersCommand;
use App\Console\Commands\SyncIcalCommand;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::command(SyncIcalCommand::class)->everyThirtyMinutes();
Schedule::command(SendCheckinRemindersCommand::class)->everyThirtyMinutes();
Schedule::command(ExpirePendingBookingsCommand::class)->everyFiveMinutes();
Schedule::command(SendPaymentRemindersCommand::class)->everyFiveMinutes();
