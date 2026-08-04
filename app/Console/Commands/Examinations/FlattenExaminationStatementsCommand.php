<?php

namespace App\Console\Commands\Examinations;

use App\Services\Examinations\ExaminationStatementFlattenService;
use Illuminate\Console\Command;
use Throwable;

class FlattenExaminationStatementsCommand extends Command
{
    protected $signature = 'examinations:flatten-statements
                            {input : Absolute or project-relative path to HEXCO statement workbook (.xls/.xlsx)}
                            {--output= : Output Exam-Dump CSV path (default: storage/app/examinations/flattened/<basename>.csv)}
                            {--only-session= : Only include subject rows for this sitting (e.g. 06/2026 or 2026-06)}
                            {--limit= : Process at most this many sheets (for smoke tests)}
                            {--start=0 : 0-based sheet index to start from}';

    protected $description = 'Flatten HEXCO Individual Statement of Results sheets into an Exam-Dump CSV for examinations:import';

    public function handle(ExaminationStatementFlattenService $service): int
    {
        $input = $this->argument('input');
        $inputPath = $this->resolvePath((string) $input);

        if (! is_file($inputPath)) {
            $this->error("Input file not found: {$inputPath}");

            return self::FAILURE;
        }

        $outputOption = $this->option('output');
        $outputPath = is_string($outputOption) && $outputOption !== ''
            ? $this->resolvePath($outputOption)
            : storage_path('app/examinations/flattened/'.pathinfo($inputPath, PATHINFO_FILENAME).'-exam-dump.csv');

        $onlySession = $this->option('only-session');
        $onlySession = is_string($onlySession) && $onlySession !== '' ? $onlySession : null;

        $limit = $this->option('limit');
        $limitSheets = is_numeric($limit) ? (int) $limit : null;
        $startSheet = max(0, (int) $this->option('start'));

        $this->info("Flattening: {$inputPath}");
        $this->info("Output: {$outputPath}");

        if ($onlySession !== null) {
            $this->info("Session filter: {$onlySession}");
        }

        try {
            $stats = $service->flattenToCsv(
                $inputPath,
                $outputPath,
                $onlySession,
                $limitSheets,
                $startSheet,
            );
        } catch (Throwable $exception) {
            $this->error($exception->getMessage());

            return self::FAILURE;
        }

        $this->info("Sheets processed: {$stats['sheets']}");
        $this->info("Sheets skipped (no parsable rows): {$stats['skipped_sheets']}");
        $this->info("Exam-Dump rows written: {$stats['rows']}");
        $this->newLine();
        $this->comment('Next: upload the CSV at /examinations/import (or drop it in storage/app/examinations/inbox).');

        return self::SUCCESS;
    }

    private function resolvePath(string $path): string
    {
        if ($path === '') {
            return $path;
        }

        if ($path[0] === '/' || preg_match('/^[A-Za-z]:[\\\\\\/]/', $path) === 1) {
            return $path;
        }

        return base_path($path);
    }
}
