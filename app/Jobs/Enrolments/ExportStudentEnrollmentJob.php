<?php

declare(strict_types=1);

namespace App\Jobs\Enrolments;

use App\Services\Enrolments\StudentEnrollmentExportService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class ExportStudentEnrollmentJob implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public readonly ?string $intakeYear = null,
        public readonly array $recipientEmails = [],
    ) {}

    public function handle(StudentEnrollmentExportService $exportService): string
    {
        return $exportService->export($this->intakeYear, $this->recipientEmails);
    }
}
