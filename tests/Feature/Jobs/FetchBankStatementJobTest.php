<?php

use App\Enums\Integrations\Banks\ZBBankStatementFetchWindowStatus;
use App\Jobs\Integrations\Banks\ZB\FetchBankStatementJob;
use App\Models\Integrations\Banks\ZBBankStatementFetchWindow;
use App\Services\Integrations\Banks\ZB\FetchBankStatementExecuteResult;
use App\Services\Integrations\Banks\ZB\FetchBankStatementService;

it('marks the window succeeded when the service completes with no persist failures', function () {
    config()->set('app.timezone', 'Africa/Harare');
    date_default_timezone_set('Africa/Harare');
    $this->travelTo('2026-01-08 12:00:00');

    $window = ZBBankStatementFetchWindow::factory()->create([
        'status' => ZBBankStatementFetchWindowStatus::Pending,
        'window_start' => '2026-01-01',
        'window_end' => '2026-01-07',
        'attempt_count' => 1,
    ]);

    $this->mock(FetchBankStatementService::class, function ($mock): void {
        $mock->shouldReceive('executeWithResult')
            ->once()
            ->andReturn(new FetchBankStatementExecuteResult(0, 0));
    });

    (new FetchBankStatementJob($window->id))->handle(app(FetchBankStatementService::class));

    $window->refresh();

    expect($window->status)->toBe(ZBBankStatementFetchWindowStatus::Succeeded)
        ->and($window->succeeded_at)->not->toBeNull()
        ->and($window->failed_at)->toBeNull();
});

it('keeps the window pending when the service completes successfully before window_end end-of-day', function () {
    config()->set('app.timezone', 'Africa/Harare');
    date_default_timezone_set('Africa/Harare');
    $this->travelTo('2026-01-07 23:00:00');

    $window = ZBBankStatementFetchWindow::factory()->create([
        'status' => ZBBankStatementFetchWindowStatus::Pending,
        'window_start' => '2026-01-01',
        'window_end' => '2026-01-07',
        'attempt_count' => 1,
    ]);

    $this->mock(FetchBankStatementService::class, function ($mock): void {
        $mock->shouldReceive('executeWithResult')
            ->once()
            ->andReturn(new FetchBankStatementExecuteResult(0, 0));
    });

    (new FetchBankStatementJob($window->id))->handle(app(FetchBankStatementService::class));

    $window->refresh();

    expect($window->status)->toBe(ZBBankStatementFetchWindowStatus::Pending)
        ->and($window->succeeded_at)->toBeNull()
        ->and($window->failed_at)->toBeNull()
        ->and($window->last_error)->toBeNull();
});

it('defers succeeded until end-of-day of created_at when window_end is extended by 1 day', function () {
    config()->set('app.timezone', 'Africa/Harare');
    date_default_timezone_set('Africa/Harare');

    $this->travelTo('2026-01-07 23:00:00');

    $window = ZBBankStatementFetchWindow::factory()->create([
        'status' => ZBBankStatementFetchWindowStatus::Pending,
        'window_start' => '2026-01-01',
        'window_end' => '2026-01-08',
        'attempt_count' => 1,
    ]);

    $this->mock(FetchBankStatementService::class, function ($mock): void {
        $mock->shouldReceive('executeWithResult')
            ->twice()
            ->andReturn(new FetchBankStatementExecuteResult(0, 0));
    });

    $this->travelTo('2026-01-07 23:30:00');
    (new FetchBankStatementJob($window->id))->handle(app(FetchBankStatementService::class));
    $window->refresh();

    expect($window->status)->toBe(ZBBankStatementFetchWindowStatus::Pending)
        ->and($window->succeeded_at)->toBeNull();

    $this->travelTo('2026-01-08 00:00:00');
    (new FetchBankStatementJob($window->id))->handle(app(FetchBankStatementService::class));
    $window->refresh();

    expect($window->status)->toBe(ZBBankStatementFetchWindowStatus::Succeeded)
        ->and($window->succeeded_at)->not->toBeNull()
        ->and($window->failed_at)->toBeNull();
});

it('marks the window failed when the API succeeds but persisting rows fails', function () {
    $window = ZBBankStatementFetchWindow::factory()->create([
        'status' => ZBBankStatementFetchWindowStatus::Pending,
        'window_start' => '2026-01-01',
        'window_end' => '2026-01-07',
        'attempt_count' => 1,
    ]);

    $this->mock(FetchBankStatementService::class, function ($mock): void {
        $mock->shouldReceive('executeWithResult')
            ->once()
            ->andReturn(new FetchBankStatementExecuteResult(0, 3));
    });

    (new FetchBankStatementJob($window->id))->handle(app(FetchBankStatementService::class));

    $window->refresh();

    expect($window->status)->toBe(ZBBankStatementFetchWindowStatus::Failed)
        ->and($window->failed_at)->not->toBeNull()
        ->and($window->last_error)->toContain('Persist failed for 3');
});

it('throws when the service reports a non-zero exit code', function () {
    $window = ZBBankStatementFetchWindow::factory()->create([
        'status' => ZBBankStatementFetchWindowStatus::Pending,
        'attempt_count' => 1,
    ]);

    $this->mock(FetchBankStatementService::class, function ($mock): void {
        $mock->shouldReceive('executeWithResult')
            ->once()
            ->andReturn(new FetchBankStatementExecuteResult(1, 0));
    });

    expect(fn () => (new FetchBankStatementJob($window->id))->handle(app(FetchBankStatementService::class)))
        ->toThrow(RuntimeException::class);

    expect($window->fresh()->status)->toBe(ZBBankStatementFetchWindowStatus::Pending);
});

it('sets the window back to pending when the service reports HTTP 401 for later dispatch', function () {
    $window = ZBBankStatementFetchWindow::factory()->create([
        'status' => ZBBankStatementFetchWindowStatus::Pending,
        'attempt_count' => 1,
        'failed_at' => null,
        'last_error' => null,
    ]);

    $this->mock(FetchBankStatementService::class, function ($mock): void {
        $mock->shouldReceive('executeWithResult')
            ->once()
            ->andReturn(new FetchBankStatementExecuteResult(1, 0, resetWindowToPendingForRetry: true));
    });

    (new FetchBankStatementJob($window->id))->handle(app(FetchBankStatementService::class));

    $window->refresh();

    expect($window->status)->toBe(ZBBankStatementFetchWindowStatus::Pending)
        ->and($window->failed_at)->toBeNull()
        ->and($window->last_error)->toContain('HTTP 401');
});

it('records failure on the window when the job fails definitively', function () {
    $window = ZBBankStatementFetchWindow::factory()->create([
        'status' => ZBBankStatementFetchWindowStatus::Pending,
        'attempt_count' => 3,
    ]);

    (new FetchBankStatementJob($window->id))->failed(new RuntimeException('Upstream timeout'));

    $window->refresh();

    expect($window->status)->toBe(ZBBankStatementFetchWindowStatus::Failed)
        ->and($window->last_error)->toContain('Upstream timeout')
        ->and($window->failed_at)->not->toBeNull();
});
