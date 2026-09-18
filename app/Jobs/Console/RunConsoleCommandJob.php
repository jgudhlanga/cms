<?php

declare(strict_types=1);

namespace App\Jobs\Console;

use App\Enums\Console\ConsoleRunStatusEnum;
use App\Models\Console\ConsoleCommandRun;
use App\Support\Console\ConsoleRunOutputStream;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Artisan;
use Throwable;

class RunConsoleCommandJob implements ShouldQueue
{
    use Queueable;

    /**
     * A mutating command must never be silently replayed.
     */
    public int $tries = 1;

    public int $timeout;

    public function __construct(
        public readonly string $runUuid,
        int $timeout = 300,
    ) {
        $this->timeout = $timeout;
        $this->onQueue((string) config('custom.console.queue', 'default'));
    }

    public function handle(): void
    {
        $run = $this->resolveRun();

        if ($run === null || $run->status !== ConsoleRunStatusEnum::QUEUED) {
            return;
        }

        $run->forceFill([
            'status' => ConsoleRunStatusEnum::RUNNING,
            'started_at' => now(),
        ])->save();

        $stream = new ConsoleRunOutputStream($run);
        $startedAt = microtime(true);

        try {
            $exitCode = Artisan::call($run->signature, $run->parameters ?? [], $stream);

            $stream->flush();

            $run->forceFill([
                'status' => $exitCode === 0 ? ConsoleRunStatusEnum::SUCCEEDED : ConsoleRunStatusEnum::FAILED,
                'exit_code' => $exitCode,
                'finished_at' => now(),
                'duration_ms' => (int) round((microtime(true) - $startedAt) * 1000),
            ])->save();
        } catch (Throwable $exception) {
            $stream->flush();

            $run->forceFill([
                'status' => ConsoleRunStatusEnum::FAILED,
                'exit_code' => $run->exit_code ?? 1,
                'error' => $exception->getMessage(),
                'finished_at' => now(),
                'duration_ms' => (int) round((microtime(true) - $startedAt) * 1000),
            ])->save();

            throw $exception;
        }
    }

    /**
     * A timeout or a worker crash never reaches the catch above, so close the
     * row out here instead of leaving it stuck on Running.
     */
    public function failed(?Throwable $exception): void
    {
        $run = $this->resolveRun();

        if ($run === null || $run->status->isTerminal()) {
            return;
        }

        $run->forceFill([
            'status' => ConsoleRunStatusEnum::FAILED,
            'error' => $exception?->getMessage() ?? __('console.run_failed_unknown'),
            'finished_at' => now(),
        ])->save();
    }

    private function resolveRun(): ?ConsoleCommandRun
    {
        return ConsoleCommandRun::query()->where('uuid', $this->runUuid)->first();
    }
}
