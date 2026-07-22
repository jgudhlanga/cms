<?php

use App\Services\Enrolments\StudentBankPaymentMatcher;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

it('ignores null and empty student numbers when matching payments in range', function (): void {
    config()->set('custom.bank-statements.plan_anchor_start', '2026-01-01');

    createBankCreditReceipt('H123', '2026-01-10 09:00:00', 'TXN-NULL-MATCH-001');

    $matcher = app(StudentBankPaymentMatcher::class);
    ['start_date' => $startDate, 'end_date' => $endDate] = $matcher->resolveDefaultDateRange();

    $results = $matcher->matchStudentNumbersInRange(
        [null, '', 'H123', null],
        $startDate,
        $endDate,
    );

    expect($results)->toBe(['H123' => true])
        ->and(array_keys($results))->toBe(['H123']);
});

it('ignores null and empty student numbers in hasPaymentForAny', function (): void {
    $matcher = app(StudentBankPaymentMatcher::class);
    $startDate = CarbonImmutable::parse('2026-01-01')->startOfDay();
    $endDate = CarbonImmutable::parse('2026-01-31')->endOfDay();

    $results = $matcher->hasPaymentForAny([null, '', 'H999'], $startDate, $endDate);

    expect($results)->toBe(['H999' => false])
        ->and(array_keys($results))->toBe(['H999']);
});
