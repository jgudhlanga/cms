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

    protected $description = 'Reclaim stale processing windows, then dispatch pending fetch jobs for windows whose window_end is on or before dispatch horizon (app timezone)';

    public function handle(): int
    {
        $limitOption = $this->option('limit');
        $limit = $limitOption !== null && $limitOption !== ''
            ? max(1, (int) $limitOption)
            : max(1, (int) config('custom.bank-statements.dispatch_limit', 100));

        $dispatched = 0;
        $timezone = (string) config('app.timezone');
        $todayDate = now($timezone)->toDateString();
        $dispatchHorizonDate = now($timezone)->addDay()->toDateString();

        DB::transaction(function () use ($limit, $dispatchHorizonDate, &$dispatched): void {
            $windows = ZBBankStatementFetchWindow::query()
                ->where('status', ZBBankStatementFetchWindowStatus::Pending)
                ->whereDate('window_end', '<=', $dispatchHorizonDate)
                ->orderBy('window_start')
                ->orderBy('account_type')
                ->limit($limit)
                ->lockForUpdate()
                ->get();

            foreach ($windows as $window) {
                FetchBankStatementJob::dispatch($window->id);
                $dispatched++;
            }
        });

        if ($dispatched > 0) {
            $this->info("Dispatched {$dispatched} bank statement fetch job(s).");
        }

        return self::SUCCESS;
    }
}
