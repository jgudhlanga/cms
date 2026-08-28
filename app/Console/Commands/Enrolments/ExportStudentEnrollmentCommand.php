<?php

declare(strict_types=1);

namespace App\Console\Commands\Enrolments;

use App\Jobs\Enrolments\ExportStudentEnrollmentJob;
use App\Services\Enrolments\StudentEnrollmentExportService;
use App\Support\Maintenance\MaintenanceExportFilters;
use App\Support\RecipientEmailParser;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

class ExportStudentEnrollmentCommand extends Command
{
    /**
     * @var string
     */
    protected $signature = 'enrolments:export-student-enrollment
                            {--sync : Run the export synchronously instead of dispatching a queued job}
                            {--intake-year= : Filter by intake period calendar year}
                            {--calendar-year= : Filter by academic calendar year on the enrolment}
                            {--semester= : Filter by semester id on the enrolment}
                            {--calendar-type= : Filter by calendar type (semester, term or abma)}
                            {--email=* : Comma-separated email address(es) to send the export to}';

    /**
     * @var string
     */
    protected $description = 'Export finalised student enrolments to Student_Enrollment.csv';

    public function handle(): int
    {
        $filters = MaintenanceExportFilters::normalizeForEnrolments([
            'intake_year' => $this->option('intake-year'),
            'calendar_year' => $this->option('calendar-year'),
            'semester_id' => $this->option('semester'),
            'calendar_type' => $this->option('calendar-type'),
        ]);

        /** @var list<string> $recipientEmails */
        $recipientEmails = RecipientEmailParser::parse($this->option('email'));

        if ($this->option('sync')) {
            $relativePath = app(StudentEnrollmentExportService::class)->export($filters, $recipientEmails);
            $this->info('Export completed: '.Storage::disk('local')->path($relativePath));

            return self::SUCCESS;
        }

        ExportStudentEnrollmentJob::dispatch($filters, $recipientEmails)->withoutDelay();
        $this->info('Student enrollment export queued.');

        return self::SUCCESS;
    }
}
