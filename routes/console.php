<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::command('statements:dispatch-fetch-jobs')->everyTenMinutes()->withoutOverlapping();

Schedule::command('statements:plan-fetch-windows')->dailyAt('00:00')->withoutOverlapping();
