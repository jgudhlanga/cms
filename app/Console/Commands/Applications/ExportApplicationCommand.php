<?php

declare(strict_types=1);

namespace App\Console\Commands\Applications;

use App\Jobs\Applications\ExportApplicationJob;
use App\Services\Applications\ApplicationExportService;
use App\Support\Maintenance\MaintenanceExportFilters;
use App\Support\RecipientEmailParser;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

class ExportApplicationCommand extends Command
{
    /**
     * @var string
     */
    protected $signature = 'applications:export
                            {--sync : Run the export synchronously instead of dispatching a queued job}
                            {--intake-year= : Filter by intake period calendar year}
                            {--intake-period= : Filter by intake period id}
                            {--applied-from= : Only include applications created on or after this date}
                            {--applied-to= : Only include applications created on or before this date}
                            {--email=* : Comma-separated email address(es) to send the export to}';

    /**
     * @var string
     */
    protected $description = 'Export accepted and enrolled student applications to Application.csv';

    public function handle(): int
    {
        $filters = MaintenanceExportFilters::normalizeForApplications([
            'intake_year' => $this->option('intake-year'),
            'intake_period_id' => $this->option('intake-period'),
            'applied_from' => $this->option('applied-from'),
            'applied_to' => $this->option('applied-to'),
        ]);

        /** @var list<string> $recipientEmails */
        $recipientEmails = RecipientEmailParser::parse($this->option('email'));

        if ($this->option('sync')) {
            $relativePath = app(ApplicationExportService::class)->export($filters, $recipientEmails);
            $this->info('Export completed: '.Storage::disk('local')->path($relativePath));

            return self::SUCCESS;
        }

        ExportApplicationJob::dispatch($filters, $recipientEmails)->withoutDelay();
        $this->info('Application export queued.');

        return self::SUCCESS;
    }
}
