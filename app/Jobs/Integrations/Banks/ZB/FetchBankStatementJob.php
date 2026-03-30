<?php

namespace App\Jobs\Integrations\Banks\ZB;

use App\Enums\Integrations\Banks\ZBBankStatementFetchWindowStatus;
use App\Models\Integrations\Banks\ZBBankStatementFetchWindow;
use App\Services\Integrations\Banks\ZB\FetchBankStatementService;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Throwable;

class FetchBankStatementJob implements ShouldBeUnique, ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public int $timeout = 300;

    /**
     * Seconds the unique lock is held (align with worst-case runtime).
     */
    public int $uniqueFor = 3600;

    public function __construct(
        public int $fetchWindowId,
    ) {
        $this->afterCommit();
        $this->onQueue((string) config('custom.bank-statements.bank_statements_queue', 'bank-statements'));
    }

    public function uniqueId(): string
    {
        return 'zb-bank-statement-fetch-window:'.$this->fetchWindowId;
    }

    public function handle(FetchBankStatementService $fetchBankStatementService): void
    {
        $window = ZBBankStatementFetchWindow::query()->find($this->fetchWindowId);
        if ($window === null) {
            Log::critical('Bank statement fetch window not found; job exiting.', [
                'fetch_window_id' => $this->fetchWindowId,
            ]);

            return;
        }

        if ($window->status !== ZBBankStatementFetchWindowStatus::Pending) {
            return;
        }

        // Count a real execution attempt (dispatcher no longer flips status).
        $window->forceFill([
            'attempt_count' => $window->attempt_count + 1,
        ])->save();

        $accountType = $window->account_type;

        /** @var CarbonInterface $windowStart */
        $windowStart = $window->window_start;

        /** @var CarbonInterface $windowEnd */
        $windowEnd = $window->window_end;

        $lastErrorMessage = null;

        $result = $fetchBankStatementService->executeWithResult(
            $accountType,
            $windowStart->format('Y-m-d'),
            $windowEnd->format('Y-m-d'),
            info: null,
            warn: null,
            error: function (string $message) use (&$lastErrorMessage): void {
                $lastErrorMessage = $message;
            },
        );

        if ($result->exitCode !== 0) {
            if ($result->resetWindowToPendingForRetry) {
                $window->forceFill([
                    'status' => ZBBankStatementFetchWindowStatus::Pending,
                    'failed_at' => null,
                    'last_error' => Str::limit(
                        'Bank statement API returned HTTP 401; deferred for a later dispatch.',
                        2000
                    ),
                ])->save();

                Log::critical('Bank statement fetch deferred for later retry (HTTP 401).', [
                    'fetch_window_id' => $this->fetchWindowId,
                    'account_type' => $accountType,
                    'last_error' => $window->last_error,
                ]);

                return;
            }

            $reason = $lastErrorMessage !== null ? Str::limit($lastErrorMessage, 2000) : null;

            throw new \RuntimeException("Failed fetching bank statement for window #{$this->fetchWindowId} ({$window->account_type}).".($reason !== null ? " {$reason}" : ''));
        }

        /**
         * Strict completion: any persist failures mark the window failed (non-retry) so operators can investigate without endless retries.
         */
        if ($result->persistFailedCount > 0) {
            $window->forceFill([
                'status' => ZBBankStatementFetchWindowStatus::Failed,
                'failed_at' => now(),
                'last_error' => Str::limit(
                    "Persist failed for {$result->persistFailedCount} statement row(s) after a successful API response.",
                    2000
                ),
            ])->save();

            Log::critical('Bank statement persist failed; window marked failed.', [
                'fetch_window_id' => $this->fetchWindowId,
                'account_type' => $accountType,
                'persist_failed_count' => $result->persistFailedCount,
                'last_error' => $window->last_error,
            ]);

            return;
        }

        $timezone = (string) config('app.timezone');
        $now = now($timezone);
        $windowEndDateString = $windowEnd->toDateString();
        $createdAtDateString = $window->created_at?->toDateString();
        $closeDateString = $windowEndDateString;

        // Legacy behavior: the planner may extend the final window by 1 day for API completeness.
        // In that case we want the window to "close" only at end-of-day of when it was planned/created.
        if ($createdAtDateString !== null && $windowEndDateString > $createdAtDateString) {
            $closeDateString = $createdAtDateString;
        }

        $closeEod = CarbonImmutable::parse($closeDateString, $timezone)->endOfDay();
        if ($now->lt($closeEod)) {
            $window->forceFill([
                'status' => ZBBankStatementFetchWindowStatus::Pending,
                'failed_at' => null,
                'last_error' => null,
            ])->save();

            return;
        }

        $window->forceFill([
            'status' => ZBBankStatementFetchWindowStatus::Succeeded,
            'succeeded_at' => now(),
            'failed_at' => null,
            'last_error' => null,
        ])->save();
    }

    /**
     * @return array<int, string>
     */
    public function tags(): array
    {
        return [
            'bank_statements',
            'zb_statement_window:'.$this->fetchWindowId,
        ];
    }

    public function failed(Throwable $exception): void
    {
        $window = ZBBankStatementFetchWindow::query()->find($this->fetchWindowId);
        if ($window === null) {
            Log::critical('Bank statement fetch job failed; window row missing.', [
                'fetch_window_id' => $this->fetchWindowId,
                'error' => $exception->getMessage(),
            ]);

            return;
        }

        if ($window->status === ZBBankStatementFetchWindowStatus::Succeeded) {
            return;
        }

        $window->forceFill([
            'status' => ZBBankStatementFetchWindowStatus::Failed,
            'failed_at' => now(),
            'last_error' => Str::limit($exception->getMessage(), 2000),
        ])->save();

        Log::critical('Bank statement fetch job failed.', [
            'fetch_window_id' => $this->fetchWindowId,
            'account_type' => $window->account_type,
            'error' => $exception->getMessage(),
        ]);
    }
}
