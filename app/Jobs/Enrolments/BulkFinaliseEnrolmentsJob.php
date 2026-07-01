<?php

declare(strict_types=1);

namespace App\Jobs\Enrolments;

use App\Services\Enrolments\BulkFinaliseEnrolmentsService;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Throwable;

class BulkFinaliseEnrolmentsJob implements ShouldQueue
{
    use Queueable;

    public int $tries;

    public int $timeout;

    public function __construct(
        public readonly string $runId,
        public readonly string $startDate,
        public readonly string $endDate,
    ) {
        $this->tries = (int) config('custom.enrolments.bulk_finalise.job_tries', 1);
        $this->timeout = (int) config('custom.enrolments.bulk_finalise.job_timeout', 3600);
    }

    public function handle(BulkFinaliseEnrolmentsService $bulkFinaliseService): void
    {
        $startDate = CarbonImmutable::parse($this->startDate);
        $endDate = CarbonImmutable::parse($this->endDate);

        try {
            $bulkFinaliseService->run($startDate, $endDate, dryRun: false, runId: $this->runId);
        } catch (Throwable $exception) {
            $bulkFinaliseService->markRunFailed($this->runId, $exception->getMessage());

            throw $exception;
        }
    }

    public function failed(?Throwable $exception): void
    {
        app(BulkFinaliseEnrolmentsService::class)->markRunFailed(
            $this->runId,
            $exception?->getMessage() ?? 'Bulk finalise enrolments job failed.',
        );
    }
}
