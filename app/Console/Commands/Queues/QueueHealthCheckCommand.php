<?php

namespace App\Console\Commands\Queues;

use Illuminate\Console\Command;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class QueueHealthCheckCommand extends Command
{
    protected $signature = 'queue:health
        {--queues= : Comma-separated queue names workers should consume (defaults to env/config)}';

    protected $description = 'Show pending/failed queue health and detect unmonitored pending queues';

    public function handle(): int
    {
        $expectedQueues = $this->expectedQueues();
        $pendingByQueue = $this->pendingByQueue();
        $failedByQueue = $this->failedByQueue();

        $this->line('Queue health overview');
        $this->newLine();

        $this->line('Expected worker queues: '.implode(', ', $expectedQueues));
        $this->newLine();

        $this->table(
            ['Queue', 'Pending jobs', 'Failed jobs'],
            $this->tableRows($pendingByQueue, $failedByQueue)
        );

        $unexpectedPendingQueues = $pendingByQueue
            ->keys()
            ->diff($expectedQueues)
            ->values()
            ->all();

        if ($unexpectedPendingQueues !== []) {
            $this->error(
                'Pending jobs exist on queue(s) not listed in expected worker queues: '
                .implode(', ', $unexpectedPendingQueues)
            );

            return self::FAILURE;
        }

        $this->info('Queue health check passed.');

        return self::SUCCESS;
    }

    /**
     * @return array<int, array<int, string|int>>
     */
    private function tableRows(Collection $pendingByQueue, Collection $failedByQueue): array
    {
        $queues = $pendingByQueue
            ->keys()
            ->merge($failedByQueue->keys())
            ->unique()
            ->sort()
            ->values();

        return $queues
            ->map(function (string $queue) use ($pendingByQueue, $failedByQueue): array {
                return [
                    $queue,
                    (int) ($pendingByQueue->get($queue) ?? 0),
                    (int) ($failedByQueue->get($queue) ?? 0),
                ];
            })
            ->all();
    }

    /**
     * @return array<int, string>
     */
    private function expectedQueues(): array
    {
        $option = trim((string) $this->option('queues'));

        if ($option !== '') {
            return collect(explode(',', $option))
                ->map(static fn (string $queue): string => trim($queue))
                ->filter()
                ->unique()
                ->values()
                ->all();
        }

        $defaultQueue = (string) config('queue.connections.database.queue', 'default');
        $bankStatementsQueue = (string) config('custom.bank-statements.bank_statements_queue', 'bank-statements');

        return collect([$defaultQueue, $bankStatementsQueue])
            ->filter()
            ->unique()
            ->values()
            ->all();
    }

    private function pendingByQueue(): Collection
    {
        return DB::table('jobs')
            ->select('queue', DB::raw('count(*) as total'))
            ->groupBy('queue')
            ->pluck('total', 'queue');
    }

    private function failedByQueue(): Collection
    {
        return DB::table('failed_jobs')
            ->select('queue', DB::raw('count(*) as total'))
            ->groupBy('queue')
            ->pluck('total', 'queue');
    }
}
