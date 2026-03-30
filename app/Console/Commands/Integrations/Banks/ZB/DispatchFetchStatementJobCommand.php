<?php

namespace App\Console\Commands\Integrations\Banks\ZB;

use App\Enums\Integrations\Banks\ZBBankStatementFetchWindowStatus;
use App\Jobs\Integrations\Banks\ZB\FetchBankStatementJob;
use App\Models\Integrations\Banks\ZBBankStatementFetchWindow;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class DispatchFetchStatementJobCommand extends Command
{
    protected $signature = 'statements:dispatch-fetch-jobs {--limit= : Max fetch jobs to enqueue this run}';

    protected $description = 'Reclaim stale processing windows, then dispatch pending fetch jobs for windows whose window_end is on or before today (app timezone)';

    public function handle(): int
    {
        $reclaimed = ZBBankStatementFetchWindow::reclaimStaleProcessing();
        if ($reclaimed > 0) {
            $this->info("Reclaimed {$reclaimed} stale processing window(s).");
        }

        $limitOption = $this->option('limit');
        $limit = $limitOption !== null && $limitOption !== ''
            ? max(1, (int) $limitOption)
            : max(1, (int) config('custom.bank-statements.dispatch_limit', 100));

        $dispatched = 0;
        $todayDate = now((string) config('app.timezone'))->toDateString();

        DB::transaction(function () use ($limit, $todayDate, &$dispatched): void {
            $windows = ZBBankStatementFetchWindow::query()
                ->where('status', ZBBankStatementFetchWindowStatus::Pending)
                ->whereDate('window_end', '<=', $todayDate)
                ->orderBy('window_start')
                ->orderBy('account_type')
                ->limit($limit)
                ->lockForUpdate()
                ->get();

            foreach ($windows as $window) {
                $window->update([
                    'status' => ZBBankStatementFetchWindowStatus::Processing,
                    'processing_started_at' => now(),
                    'attempt_count' => $window->attempt_count + 1,
                ]);

                FetchBankStatementJob::dispatch($window->id);
                $dispatched++;
                $this->info("Dispatched bank statement fetch job for window #{$window->id} ({$window->account_type}, {$window->window_start->toDateString()}–{$window->window_end->toDateString()}).");
            }
        });

        if ($dispatched === 0) {
            $this->info('No pending bank statement fetch windows to dispatch.');
        }

        return self::SUCCESS;
    }
}
