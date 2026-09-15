<?php

use App\Models\Students\StudentApplication;
use App\Observers\Students\StudentApplicationObserver;
use Illuminate\Support\Carbon;

afterEach(function () {
    Carbon::setTestNow();
});

test('application tracking numbers use the creation date, not the date config was cached', function () {
    config([
        'custom.system.application-tracking-number-prefix' => 'TN',
        'custom.system.application-tracking-number-suffix' => '',
    ]);

    $trackingNumberAt = function (string $moment): string {
        Carbon::setTestNow($moment);

        $application = new StudentApplication;
        $application->id = 42;
        (new StudentApplicationObserver)->creating($application);

        return $application->application_tracking_number;
    };

    // Format: prefix + two-digit year + id + 12-hour hour and minutes + suffix.
    expect($trackingNumberAt('2026-01-15 09:05:00'))->toBe('TN26420905')
        ->and($trackingNumberAt('2027-03-02 14:30:00'))->toBe('TN27420230');
});
