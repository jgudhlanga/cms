<?php

use App\Enums\Assessments\MissingMarksNotificationTierEnum;
use App\Models\Institution\AssessmentCalendar\AssessmentCalendar;
use Carbon\Carbon;

test('assessment calendar computes notification dates from end date intervals', function () {
    $calendar = new AssessmentCalendar([
        'end_date' => '2026-04-20',
        'first_notification_days_before' => 10,
        'second_notification_days_before' => 5,
        'due_notification_days_before' => 0,
    ]);

    expect($calendar->first_notification_date?->toDateString())->toBe('2026-04-10')
        ->and($calendar->second_notification_date?->toDateString())->toBe('2026-04-15')
        ->and($calendar->due_notification_date?->toDateString())->toBe('2026-04-20')
        ->and($calendar->matchingTier(Carbon::parse('2026-04-10')))->toBe(MissingMarksNotificationTierEnum::First)
        ->and($calendar->matchingTier(Carbon::parse('2026-04-15')))->toBe(MissingMarksNotificationTierEnum::Second)
        ->and($calendar->matchingTier(Carbon::parse('2026-04-20')))->toBe(MissingMarksNotificationTierEnum::Due)
        ->and($calendar->matchingTier(Carbon::parse('2026-04-12')))->toBeNull()
        ->and($calendar->isInNotificationWindow(Carbon::parse('2026-04-10')))->toBeTrue()
        ->and($calendar->isInNotificationWindow(Carbon::parse('2026-04-20')))->toBeTrue()
        ->and($calendar->isInNotificationWindow(Carbon::parse('2026-04-09')))->toBeFalse();
});

test('matching tier prefers the first matching case when intervals collide', function () {
    $calendar = new AssessmentCalendar([
        'end_date' => '2026-04-20',
        'first_notification_days_before' => 0,
        'second_notification_days_before' => 0,
        'due_notification_days_before' => 0,
    ]);

    expect($calendar->matchingTier(Carbon::parse('2026-04-20')))->toBe(MissingMarksNotificationTierEnum::First);
});
