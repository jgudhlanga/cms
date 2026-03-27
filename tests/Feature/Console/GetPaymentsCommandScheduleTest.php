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

it('guards payments dispatcher schedule behind non-production environment check', function () {
    $consoleRoutes = file_get_contents(base_path('routes/console.php'));

    expect($consoleRoutes)
        ->toContain("if (! app()->environment('production'))")
        ->toContain("Schedule::command('payments:dispatch')");
});
