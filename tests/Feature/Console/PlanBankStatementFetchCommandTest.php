<?php

use App\Enums\Integrations\Banks\ZBBankStatementFetchWindowStatus;
use App\Models\Integrations\Banks\ZBBankStatementFetchWindow;
use Illuminate\Support\Facades\DB;

it('inserts pending fetch windows for each account type and weekly slice', function () {
    $this->travelTo('2026-01-14 12:00:00');

    config()->set('custom.bank-statements.chunk_days', 7);
    config()->set('custom.bank-statements.account_types', ['usd', 'zwg']);
    config()->set('custom.bank-statements.plan_anchor_start', '2026-01-01');

    $this->artisan('statements:plan-fetch-windows')->assertSuccessful();

    expect(ZBBankStatementFetchWindow::query()->count())->toBe(4);

    expect(
        ZBBankStatementFetchWindow::query()
            ->where('account_type', 'usd')
            ->whereDate('window_start', '2026-01-01')
            ->whereDate('window_end', '2026-01-07')
            ->where('status', ZBBankStatementFetchWindowStatus::Pending)
            ->exists()
    )->toBeTrue();
});

it('does not duplicate or overwrite succeeded windows on replan', function () {
    $this->travelTo('2026-01-07 12:00:00');

    config()->set('custom.bank-statements.chunk_days', 7);
    config()->set('custom.bank-statements.account_types', ['usd']);
    config()->set('custom.bank-statements.plan_anchor_start', '2026-01-01');

    ZBBankStatementFetchWindow::query()->create([
        'account_type' => 'usd',
        'window_start' => '2026-01-01',
        'window_end' => '2026-01-07',
        'status' => ZBBankStatementFetchWindowStatus::Succeeded,
        'attempt_count' => 1,
        'succeeded_at' => now(),
    ]);

    $this->artisan('statements:plan-fetch-windows')->assertSuccessful();

    $row = ZBBankStatementFetchWindow::query()
        ->where('account_type', 'usd')
        ->whereDate('window_start', '2026-01-01')
        ->whereDate('window_end', '2026-01-07')
        ->first();

    expect($row)->not->toBeNull()
        ->and($row->status)->toBe(ZBBankStatementFetchWindowStatus::Succeeded)
        ->and(ZBBankStatementFetchWindow::query()->count())->toBe(1);
});

it('bulk inserts using plan_insert_chunk without failing', function () {
    $this->travelTo('2022-12-31 12:00:00');

    config()->set('custom.bank-statements.chunk_days', 7);
    config()->set('custom.bank-statements.account_types', ['usd']);
    config()->set('custom.bank-statements.plan_insert_chunk', 100);
    config()->set('custom.bank-statements.plan_anchor_start', '2020-01-01');

    $this->artisan('statements:plan-fetch-windows')->assertSuccessful();

    expect(ZBBankStatementFetchWindow::query()->count())->toBeGreaterThan(100);
    expect(DB::table('zb_bank_statement_fetch_windows')->where('status', 'pending')->count())
        ->toBe(ZBBankStatementFetchWindow::query()->count());
});

it('extends the final slice by one day when shorter than chunk_days', function () {
    $this->travelTo('2026-01-10 12:00:00');

    config()->set('custom.bank-statements.chunk_days', 7);
    config()->set('custom.bank-statements.account_types', ['usd']);
    config()->set('custom.bank-statements.plan_anchor_start', '2026-01-01');

    $this->artisan('statements:plan-fetch-windows')->assertSuccessful();

    expect(
        ZBBankStatementFetchWindow::query()
            ->where('account_type', 'usd')
            ->whereDate('window_start', '2026-01-08')
            ->whereDate('window_end', '2026-01-11')
            ->where('status', ZBBankStatementFetchWindowStatus::Pending)
            ->exists()
    )->toBeTrue();
});
