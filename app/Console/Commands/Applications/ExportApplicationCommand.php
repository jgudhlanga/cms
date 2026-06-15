<?php

declare(strict_types=1);

namespace App\Console\Commands\Applications;

use App\Jobs\Applications\ExportApplicationJob;
use App\Services\Applications\ApplicationExportService;
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
                            {--email=* : Comma-separated email address(es) to send the export to}';

    /**
     * @var string
     */
    protected $description = 'Export accepted and enrolled student applications to Application.csv';

    public function handle(): int
    {
        $intakeYear = $this->option('intake-year');
        $intakeYear = is_string($intakeYear) && $intakeYear !== '' ? $intakeYear : null;

        /** @var list<string> $recipientEmails */
        $recipientEmails = RecipientEmailParser::parse($this->option('email'));

        if ($this->option('sync')) {
            $relativePath = app(ApplicationExportService::class)->export($intakeYear, $recipientEmails);
            $this->info('Export completed: '.Storage::disk('local')->path($relativePath));

            return self::SUCCESS;
        }

        ExportApplicationJob::dispatch($intakeYear, $recipientEmails)->withoutDelay();
        $this->info('Application export queued.');

        return self::SUCCESS;
    }
}
