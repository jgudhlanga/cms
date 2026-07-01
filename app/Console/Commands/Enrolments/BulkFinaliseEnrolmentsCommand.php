<?php

declare(strict_types=1);

namespace App\Console\Commands\Enrolments;

use App\Services\Enrolments\BulkFinaliseEnrolmentsService;
use Illuminate\Console\Command;

class BulkFinaliseEnrolmentsCommand extends Command
{
    /**
     * @var string
     */
    protected $signature = 'enrolments:bulk-finalise-enrolments-command
                            {--start-date= : Inclusive payment start date (Y-m-d). Defaults to custom.bank-statements.plan_anchor_start}
                            {--end-date= : Inclusive payment end date (Y-m-d). Defaults to now()}
                            {--dry-run : Preview results without writing changes; still generates a report and emails it}';

    /**
     * @var string
     */
    protected $description = 'Bulk finalise enrolments';

    public function handle(BulkFinaliseEnrolmentsService $bulkFinaliseService): int
    {
        $dryRun = (bool) $this->option('dry-run');
        $timezone = (string) config('app.timezone');
        $startDateInput = $this->option('start-date');
        $endDateInput = $this->option('end-date');
        $startDateInput = is_string($startDateInput) && $startDateInput !== '' ? $startDateInput : null;
        $endDateInput = is_string($endDateInput) && $endDateInput !== '' ? $endDateInput : null;

        ['start_date' => $startDate, 'end_date' => $endDate] = $bulkFinaliseService->resolveDateRange(
            $startDateInput,
            $endDateInput,
        );

        $studentApplications = $bulkFinaliseService->loadVerifiedStudentApplications();

        $this->output->progressStart($studentApplications->count());

        $result = $bulkFinaliseService->run(
            $startDate,
            $endDate,
            $dryRun,
            onProgress: fn (): mixed => $this->output->progressAdvance(),
        );

        $this->output->progressFinish();
        $this->newLine();

        if ($result->aborted) {
            $this->error($result->abortMessage ?? 'Bulk finalise enrolments failed.');

            return self::FAILURE;
        }

        $this->info("Successfully finalised: {$result->successfulFinalised}");
        $this->warn("Failed finalisations: {$result->failedFinalisations}");

        if ($result->reportPath !== null) {
            $this->info("Report saved: {$result->reportPath}");
            logger()->info('Bulk finalise enrolments report saved.', [
                'path' => $result->reportPath,
                'dry_run' => $dryRun,
                'successful_finalised' => $result->successfulFinalised,
                'failed_finalisations' => $result->failedFinalisations,
            ]);
        }

        return self::SUCCESS;
    }
}
