<?php

namespace App\Services\Examinations;

use App\Support\Examinations\ExaminationDumpColumns;
use App\Support\Examinations\ExaminationStatementSheetParser;
use Illuminate\Support\Facades\Process;
use InvalidArgumentException;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Reader\IReader;
use RuntimeException;
use Throwable;

class ExaminationStatementFlattenService
{
    public function __construct(
        private readonly ExaminationStatementSheetParser $parser,
    ) {}

    /**
     * @return array{sheets: int, rows: int, skipped_sheets: int, output: string}
     */
    public function flattenToCsv(
        string $inputPath,
        string $outputPath,
        ?string $onlySession = null,
        ?int $limitSheets = null,
        int $startSheet = 0,
    ): array {
        if (! is_file($inputPath)) {
            throw new InvalidArgumentException("Input file not found: {$inputPath}");
        }

        $extension = strtolower(pathinfo($inputPath, PATHINFO_EXTENSION));
        $directory = dirname($outputPath);

        if (! is_dir($directory) && ! mkdir($directory, 0775, true) && ! is_dir($directory)) {
            throw new RuntimeException("Could not create output directory: {$directory}");
        }

        $handle = fopen($outputPath, 'wb');

        if ($handle === false) {
            throw new RuntimeException("Could not open output file for writing: {$outputPath}");
        }

        try {
            fputcsv($handle, ExaminationDumpColumns::requiredHeaders());

            $stats = [
                'sheets' => 0,
                'rows' => 0,
                'skipped_sheets' => 0,
                'output' => $outputPath,
            ];

            $onSheet = function (array $grid) use ($handle, $onlySession, &$stats): void {
                $stats['sheets']++;
                $dumpRows = $this->parser->parse($grid, $onlySession);

                if ($dumpRows === []) {
                    $stats['skipped_sheets']++;

                    return;
                }

                foreach ($dumpRows as $dumpRow) {
                    $ordered = [];
                    foreach (ExaminationDumpColumns::requiredHeaders() as $header) {
                        $ordered[] = $dumpRow[$header] ?? '';
                    }
                    fputcsv($handle, $ordered);
                    $stats['rows']++;
                }
            };

            if ($extension === 'xls') {
                $this->iterateXlsViaPython($inputPath, $onSheet, $limitSheets, $startSheet);
            } else {
                $this->iterateViaPhpSpreadsheet($inputPath, $onSheet, $limitSheets, $startSheet);
            }

            return $stats;
        } finally {
            fclose($handle);
        }
    }

    /**
     * @param  callable(list<list<mixed>>): void  $onSheet
     */
    private function iterateXlsViaPython(
        string $inputPath,
        callable $onSheet,
        ?int $limitSheets,
        int $startSheet,
    ): void {
        $script = base_path('scripts/examinations/extract_hexco_xls_sheets.py');
        $python = $this->resolvePython();
        $ndjsonPath = storage_path('app/examinations/flattened/.extract-'.uniqid('', true).'.ndjson');
        $directory = dirname($ndjsonPath);

        if (! is_dir($directory) && ! mkdir($directory, 0775, true) && ! is_dir($directory)) {
            throw new RuntimeException("Could not create directory: {$directory}");
        }

        $command = [
            $python,
            $script,
            $inputPath,
            '--output',
            $ndjsonPath,
            '--start',
            (string) max(0, $startSheet),
        ];

        if ($limitSheets !== null) {
            $command[] = '--limit';
            $command[] = (string) max(0, $limitSheets);
        }

        try {
            $result = Process::timeout(3600)->run($command);

            if ($result->failed()) {
                $error = trim($result->errorOutput()) ?: trim($result->output()) ?: 'unknown error';

                throw new RuntimeException(
                    "Failed to extract .xls sheets via Python/xlrd: {$error}"
                );
            }

            if (! is_file($ndjsonPath)) {
                throw new RuntimeException('Sheet extractor did not produce an NDJSON file.');
            }

            $handle = fopen($ndjsonPath, 'rb');

            if ($handle === false) {
                throw new RuntimeException("Could not read NDJSON extract: {$ndjsonPath}");
            }

            try {
                while (($line = fgets($handle)) !== false) {
                    $this->handleNdjsonLine($line, $onSheet);
                }
            } finally {
                fclose($handle);
            }
        } finally {
            if (is_file($ndjsonPath)) {
                @unlink($ndjsonPath);
            }
        }
    }

    /**
     * @param  callable(list<list<mixed>>): void  $onSheet
     */
    private function handleNdjsonLine(string $line, callable $onSheet): void
    {
        $line = trim($line);

        if ($line === '') {
            return;
        }

        try {
            /** @var array{rows?: list<list<mixed>>}|null $payload */
            $payload = json_decode($line, true, 512, JSON_THROW_ON_ERROR);
        } catch (Throwable $exception) {
            throw new RuntimeException('Invalid NDJSON from sheet extractor: '.$exception->getMessage(), 0, $exception);
        }

        if (! is_array($payload) || ! isset($payload['rows']) || ! is_array($payload['rows'])) {
            return;
        }

        /** @var list<list<mixed>> $rows */
        $rows = $payload['rows'];
        $onSheet($rows);
    }

    /**
     * @param  callable(list<list<mixed>>): void  $onSheet
     */
    private function iterateViaPhpSpreadsheet(
        string $inputPath,
        callable $onSheet,
        ?int $limitSheets,
        int $startSheet,
    ): void {
        $reader = IOFactory::createReaderForFile($inputPath);
        $reader->setReadDataOnly(true);

        $infos = $reader->listWorksheetInfo($inputPath);
        $names = array_column($infos, 'worksheetName');
        $names = array_slice($names, max(0, $startSheet), $limitSheets);

        foreach ($names as $name) {
            if ($reader instanceof IReader) {
                $reader->setLoadSheetsOnly([$name]);
            }

            $spreadsheet = $reader->load($inputPath);
            $sheet = $spreadsheet->getSheetByName($name) ?? $spreadsheet->getActiveSheet();
            $grid = $sheet->toArray(null, true, true, false);
            $spreadsheet->disconnectWorksheets();
            unset($spreadsheet);

            /** @var list<list<mixed>> $grid */
            $onSheet(array_values($grid));
        }
    }

    private function resolvePython(): string
    {
        $candidates = [
            base_path('scripts/examinations/.venv/bin/python'),
            storage_path('app/.venv-xls/bin/python'),
            'python3',
            'python',
        ];

        foreach ($candidates as $candidate) {
            if (str_contains($candidate, DIRECTORY_SEPARATOR) && ! is_file($candidate)) {
                continue;
            }

            $result = Process::timeout(10)->run([$candidate, '-c', 'import xlrd']);

            if ($result->successful()) {
                return $candidate;
            }
        }

        throw new RuntimeException(
            'Python with xlrd is required to flatten classic .xls statement workbooks. '
            .'Create a venv and install xlrd, e.g.: '
            .'python3 -m venv scripts/examinations/.venv && scripts/examinations/.venv/bin/pip install xlrd'
        );
    }
}
