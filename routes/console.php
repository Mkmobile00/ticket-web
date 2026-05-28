<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Free seats from abandoned (unpaid) bookings every minute.
Schedule::command('bookings:release-abandoned')->everyMinute()->withoutOverlapping();
