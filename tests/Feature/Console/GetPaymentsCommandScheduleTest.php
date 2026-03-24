<?php

use Illuminate\Support\Facades\Artisan;

it('registers a single queued payments dispatcher schedule', function () {
    Artisan::call('schedule:list');

    $output = Artisan::output();

    expect($output)
        ->toContain('*/10 * * * *')
        ->toContain('payments:dispatch')
        ->not->toContain('app:get-payments-command usd all');
});
