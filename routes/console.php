<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;
use App\Jobs\Maintenance\ArchiveCompletedOrders;
use App\Jobs\Maintenance\CleanupOldSessions;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::command('app:send-daily-report')->dailyAt('23:55');
Schedule::job(new ArchiveCompletedOrders)->dailyAt('02:00');
Schedule::job(new CleanupOldSessions)->weeklyOn(1, '03:00');
