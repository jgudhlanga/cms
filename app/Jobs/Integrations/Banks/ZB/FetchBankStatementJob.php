<?php

namespace App\Jobs\Integrations\Banks\ZB;

use App\Enums\Integrations\Banks\ZBBankStatementFetchWindowStatus;
use App\Models\Integrations\Banks\ZBBankStatementFetchWindow;
use App\Services\Integrations\Banks\ZB\FetchBankStatementService;
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
            Log::error('Bank statement fetch window not found; job exiting.', [
                'fetch_window_id' => $this->fetchWindowId,
            ]);

            return;
        }

        if ($window->status !== ZBBankStatementFetchWindowStatus::Processing) {
            Log::warning('Bank statement fetch window is not in processing state; skipping job.', [
                'fetch_window_id' => $this->fetchWindowId,
                'account_type' => $window->account_type,
                'status' => $window->status->value,
            ]);

            return;
        }

        $accountType = $window->account_type;

        $result = $fetchBankStatementService->executeWithResult(
            $accountType,
            $window->window_start->format('Y-m-d'),
            $window->window_end->format('Y-m-d'),
            info: function (string $message) use ($accountType): void {
                Log::info($message, [
                    'fetch_window_id' => $this->fetchWindowId,
                    'account_type' => $accountType,
                ]);
            },
            warn: function (string $message) use ($accountType): void {
                Log::warning($message, [
                    'fetch_window_id' => $this->fetchWindowId,
                    'account_type' => $accountType,
                ]);
            },
            error: function (string $message) use ($accountType): void {
                Log::error($message, [
                    'fetch_window_id' => $this->fetchWindowId,
                    'account_type' => $accountType,
                ]);
            },
        );

        if ($result->exitCode !== 0) {
            if ($result->resetWindowToPendingForRetry) {
                $window->forceFill([
                    'status' => ZBBankStatementFetchWindowStatus::Pending,
                    'processing_started_at' => null,
                    'failed_at' => null,
                    'last_error' => Str::limit(
                        'Bank statement API returned HTTP 401; deferred for a later dispatch.',
                        2000
                    ),
                ])->save();

                return;
            }

            throw new \RuntimeException("Failed fetching bank statement for window #{$this->fetchWindowId} ({$window->account_type}).");
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
                'processing_started_at' => null,
            ])->save();

            return;
        }

        $window->forceFill([
            'status' => ZBBankStatementFetchWindowStatus::Succeeded,
            'succeeded_at' => now(),
            'failed_at' => null,
            'last_error' => null,
            'processing_started_at' => null,
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
            Log::error('Bank statement fetch job failed; window row missing.', [
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
            'processing_started_at' => null,
        ])->save();

        Log::error('Bank statement fetch job failed.', [
            'fetch_window_id' => $this->fetchWindowId,
            'account_type' => $window->account_type,
            'error' => $exception->getMessage(),
        ]);
    }
}
