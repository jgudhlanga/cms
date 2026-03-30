<?php

use App\Enums\Integrations\Banks\ZBBankStatementFetchWindowStatus;
use App\Jobs\Integrations\Banks\ZB\FetchBankStatementJob;
use App\Models\Integrations\Banks\ZBBankStatementFetchWindow;
use Illuminate\Support\Facades\Queue;

it('dispatches pending windows up to the limit without flipping status', function () {
    $this->travelTo('2026-02-01 12:00:00');

    config()->set('custom.bank-statements.bank_statements_queue', 'bank-statements');

    Queue::fake();

    ZBBankStatementFetchWindow::query()->create([
        'account_type' => 'usd',
        'window_start' => '2026-01-01',
        'window_end' => '2026-01-07',
        'status' => ZBBankStatementFetchWindowStatus::Pending,
        'attempt_count' => 1,
    ]);

    ZBBankStatementFetchWindow::query()->create([
        'account_type' => 'usd',
        'window_start' => '2026-01-08',
        'window_end' => '2026-01-14',
        'status' => ZBBankStatementFetchWindowStatus::Pending,
        'attempt_count' => 0,
    ]);

    $this->artisan('statements:dispatch-fetch-jobs', [
        '--limit' => 1,
    ])->assertSuccessful();

    $jan1 = ZBBankStatementFetchWindow::query()
        ->whereDate('window_start', '2026-01-01')
        ->first();

    $jan8 = ZBBankStatementFetchWindow::query()
        ->whereDate('window_start', '2026-01-08')
        ->first();

    expect($jan1)->not->toBeNull()
        ->and($jan1->status)->toBe(ZBBankStatementFetchWindowStatus::Pending)
        ->and($jan1->attempt_count)->toBe(1);

    expect($jan8)->not->toBeNull()
        ->and($jan8->status)->toBe(ZBBankStatementFetchWindowStatus::Pending)
        ->and($jan8->attempt_count)->toBe(0);

    Queue::assertPushed(FetchBankStatementJob::class, 1);
    Queue::assertPushed(FetchBankStatementJob::class, function (FetchBankStatementJob $job) use ($jan1): bool {
        return $job->fetchWindowId === $jan1->id
            && $job->queue === 'bank-statements';
    });
});

it('uses dispatch_limit from config when option is omitted', function () {
    $this->travelTo('2026-03-01 12:00:00');

    config()->set('custom.bank-statements.bank_statements_queue', 'bank-statements');
    config()->set('custom.bank-statements.dispatch_limit', 2);

    Queue::fake();

    ZBBankStatementFetchWindow::factory()
        ->count(3)
        ->sequence(
            ['window_start' => '2026-02-01', 'window_end' => '2026-02-07'],
            ['window_start' => '2026-02-08', 'window_end' => '2026-02-14'],
            ['window_start' => '2026-02-15', 'window_end' => '2026-02-21'],
        )
        ->create();

    $this->artisan('statements:dispatch-fetch-jobs')->assertSuccessful();

    Queue::assertPushed(FetchBankStatementJob::class, 2);
});

it('does not dispatch pending windows whose window_end is after dispatch horizon (today + 1 day)', function () {
    $this->travelTo('2026-01-10 12:00:00');

    config()->set('custom.bank-statements.bank_statements_queue', 'bank-statements');

    Queue::fake();

    ZBBankStatementFetchWindow::query()->create([
        'account_type' => 'usd',
        'window_start' => '2026-01-08',
        'window_end' => '2026-01-12',
        'status' => ZBBankStatementFetchWindowStatus::Pending,
        'attempt_count' => 0,
    ]);

    $this->artisan('statements:dispatch-fetch-jobs', [
        '--limit' => 5,
    ])->assertSuccessful();

    Queue::assertNothingPushed();
});

it('dispatches pending windows once window_end is on or before dispatch horizon (today + 1 day)', function () {
    $this->travelTo('2026-01-10 12:00:00');

    config()->set('custom.bank-statements.bank_statements_queue', 'bank-statements');

    Queue::fake();

    ZBBankStatementFetchWindow::query()->create([
        'account_type' => 'usd',
        'window_start' => '2026-01-08',
        'window_end' => '2026-01-11',
        'status' => ZBBankStatementFetchWindowStatus::Pending,
        'attempt_count' => 0,
    ]);

    $this->artisan('statements:dispatch-fetch-jobs', [
        '--limit' => 5,
    ])->assertSuccessful();

    Queue::assertPushed(FetchBankStatementJob::class, 1);
});
