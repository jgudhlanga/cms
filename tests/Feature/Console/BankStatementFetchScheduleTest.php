<?php

use Illuminate\Support\Facades\Artisan;

it('registers the bank statement fetch dispatcher on the schedule', function () {
    Artisan::call('schedule:list');

    $output = Artisan::output();

    expect($output)->toContain('statements:dispatch-fetch-jobs');
    expect($output)->toMatch('/\*\s*\/10\s+\*\s+\*\s+\*\s+\*\s+php artisan statements:dispatch-fetch-jobs/');
});

it('registers the bank statement plan-fetch-windows command daily at midnight', function () {
    Artisan::call('schedule:list');

    $output = Artisan::output();

    expect($output)->toContain('statements:plan-fetch-windows');
    expect($output)->toMatch('/0\s+0\s+\*\s+\*\s+\*\s+php artisan statements:plan-fetch-windows/');
});
