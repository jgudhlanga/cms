<?php

use App\Enums\Integrations\Banks\ZBBankStatementFetchWindowStatus;
use App\Jobs\Integrations\Banks\ZB\FetchBankStatementJob;
use App\Models\Integrations\Banks\ZBBankStatementFetchWindow;
use App\Services\Integrations\Banks\ZB\FetchBankStatementExecuteResult;
use App\Services\Integrations\Banks\ZB\FetchBankStatementService;

it('marks the window succeeded when the service completes with no persist failures', function () {
    $window = ZBBankStatementFetchWindow::factory()->create([
        'status' => ZBBankStatementFetchWindowStatus::Processing,
        'window_start' => '2026-01-01',
        'window_end' => '2026-01-07',
        'attempt_count' => 1,
        'processing_started_at' => now(),
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
        ->and($window->processing_started_at)->toBeNull();
});

it('marks the window failed when the API succeeds but persisting rows fails', function () {
    $window = ZBBankStatementFetchWindow::factory()->create([
        'status' => ZBBankStatementFetchWindowStatus::Processing,
        'window_start' => '2026-01-01',
        'window_end' => '2026-01-07',
        'attempt_count' => 1,
        'processing_started_at' => now(),
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
        'status' => ZBBankStatementFetchWindowStatus::Processing,
        'attempt_count' => 1,
        'processing_started_at' => now(),
    ]);

    $this->mock(FetchBankStatementService::class, function ($mock): void {
        $mock->shouldReceive('executeWithResult')
            ->once()
            ->andReturn(new FetchBankStatementExecuteResult(1, 0));
    });

    expect(fn () => (new FetchBankStatementJob($window->id))->handle(app(FetchBankStatementService::class)))
        ->toThrow(RuntimeException::class);

    expect($window->fresh()->status)->toBe(ZBBankStatementFetchWindowStatus::Processing);
});

it('sets the window back to pending when the service reports HTTP 401 for later dispatch', function () {
    $window = ZBBankStatementFetchWindow::factory()->create([
        'status' => ZBBankStatementFetchWindowStatus::Processing,
        'attempt_count' => 1,
        'processing_started_at' => now(),
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
        ->and($window->processing_started_at)->toBeNull()
        ->and($window->failed_at)->toBeNull()
        ->and($window->last_error)->toContain('HTTP 401');
});

it('records failure on the window when the job fails definitively', function () {
    $window = ZBBankStatementFetchWindow::factory()->create([
        'status' => ZBBankStatementFetchWindowStatus::Processing,
        'attempt_count' => 3,
        'processing_started_at' => now(),
    ]);

    (new FetchBankStatementJob($window->id))->failed(new RuntimeException('Upstream timeout'));

    $window->refresh();

    expect($window->status)->toBe(ZBBankStatementFetchWindowStatus::Failed)
        ->and($window->last_error)->toContain('Upstream timeout')
        ->and($window->processing_started_at)->toBeNull();
});
