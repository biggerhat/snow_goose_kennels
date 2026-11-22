<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Daily reminders for the owner. Needs the standard Laravel cron entry:
// * * * * * cd /path/to/site && php artisan schedule:run >> /dev/null 2>&1
Schedule::command('kennel:reminders')->dailyAt('08:00');

// Lets `php artisan kennel:check` confirm that cron is actually running.
Schedule::call(fn () => Cache::forever('kennel.heartbeat', now()->toIso8601String()))->everyMinute()->name('kennel-heartbeat');
