<?php

declare(strict_types=1);

namespace App\Services\Institution;

use App\Importers\Institution\CourseSyllabusImporter;
use App\Importers\Institution\CourseSyllabusModuleImporter;
use App\Models\Institution\InstitutionDepartment;
use App\Models\Institution\Syllabus\CourseSyllabus;
use App\Models\Institution\Syllabus\CourseSyllabusImportLog;
use App\Models\Institution\Syllabus\CourseSyllabusModule;
use App\Models\Users\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use LaravelIngest\Enums\IngestStatus;
use LaravelIngest\Models\IngestRun;
use LaravelIngest\Services\RowProcessor;
use Spatie\SimpleExcel\SimpleExcelReader;
use Throwable;

class CourseSyllabusImportService
{
    private const string PREVIEW_CACHE_PREFIX = 'course-syllabus-import-preview:';

    private const int PREVIEW_TTL_MINUTES = 30;

    public function __construct(
        private readonly CourseSyllabusImportTemplateService $templateService,
    ) {}

    /**
     * @return array{
     *     previewToken: string,
     *     fileName: string,
     *     summary: array{total: int, syllabusCreates: int, syllabusUpdates: int, syllabusSkips: int, syllabusFails: int, moduleCreates: int, moduleUpdates: int, moduleSkips: int, moduleFails: int, failed: int},
     *     lookups: array{levels: list<string>, courses: list<string>, levelCourses: list<string>, semesters: list<string>},
     *     rows: list<array<string, mixed>>,
     * }
     */
    public function preview(int $institutionDepartmentId, UploadedFile $file): array
    {
        $context = $this->resolveDepartmentContext($institutionDepartmentId);
        $storedPath = $file->store('course-syllabus-imports/previews', 'ingest');
        $fullPath = Storage::disk('ingest')->path($storedPath);

        $analysis = $this->analyseFile(
            $fullPath,
            $context['tenantId'],
            $context['institutionDepartmentId'],
            dryRun: true,
        );

        $templateData = $this->templateService->assemble($institutionDepartmentId);

        $user = Auth::user();
        $previewToken = Str::random(40);

        Cache::put(
            self::PREVIEW_CACHE_PREFIX.$previewToken,
            [
                'path' => $storedPath,
                'tenant_id' => $context['tenantId'],
                'institution_department_id' => $context['institutionDepartmentId'],
                'user_id' => $user instanceof User ? $user->id : null,
                'original_filename' => $file->getClientOriginalName(),
            ],
            now()->addMinutes(self::PREVIEW_TTL_MINUTES),
        );

        return [
            'previewToken' => $previewToken,
            'fileName' => $file->getClientOriginalName(),
            'summary' => $analysis['summary'],
            'lookups' => [
                'levels' => $templateData['lookups']['levels'],
                'courses' => $templateData['lookups']['courses'],
                'levelCourses' => $templateData['lookups']['level_courses'],
                'semesters' => $templateData['lookups']['semesters'],
            ],
            'rows' => $analysis['rows'],
        ];
    }

    /**
     * @return array{
     *     ingestRunId: int,
     *     importLogId: int,
     *     rowsTotal: int,
     *     rowsSucceeded: int,
     *     rowsFailed: int,
     *     rowsSkipped: int,
     * }
     */
    /**
     * @param  array<int|string, array<string, mixed>>|null  $rowCorrections
     * @param  list<int>|null  $excludedRowNumbers
     */
    public function processFromPreview(
        int $institutionDepartmentId,
        string $previewToken,
        ?array $rowCorrections = null,
        ?array $excludedRowNumbers = null,
    ): array {
        $context = $this->resolveDepartmentContext($institutionDepartmentId);
        $preview = Cache::get(self::PREVIEW_CACHE_PREFIX.$previewToken);

        if (! is_array($preview)) {
            throw ValidationException::withMessages([
                'preview_token' => [__('syllabus.import_preview_expired')],
            ]);
        }

        $user = Auth::user();
        $userId = $user instanceof User ? $user->id : null;

        if ($userId === null || (int) ($preview['user_id'] ?? 0) !== $userId) {
            throw ValidationException::withMessages([
                'preview_token' => [__('syllabus.import_preview_expired')],
            ]);
        }

        if ((int) ($preview['institution_department_id'] ?? 0) !== $context['institutionDepartmentId']) {
            throw ValidationException::withMessages([
                'preview_token' => [__('syllabus.import_preview_mismatch')],
            ]);
        }

        $storedPath = (string) ($preview['path'] ?? '');
        $fullPath = Storage::disk('ingest')->path($storedPath);

        if (! Storage::disk('ingest')->exists($storedPath)) {
            throw ValidationException::withMessages([
                'preview_token' => [__('syllabus.import_preview_expired')],
            ]);
        }

        try {
            return $this->processStoredFile(
                $context['tenantId'],
                $context['institutionDepartmentId'],
                $fullPath,
                (string) ($preview['original_filename'] ?? 'import.xlsx'),
                $rowCorrections,
                $excludedRowNumbers,
            );
        } finally {
            Cache::forget(self::PREVIEW_CACHE_PREFIX.$previewToken);
            Storage::disk('ingest')->delete($storedPath);
        }
    }

    /**
     * @return array{
     *     ingestRunId: int,
     *     importLogId: int,
     *     rowsTotal: int,
     *     rowsSucceeded: int,
     *     rowsFailed: int,
     *     rowsSkipped: int,
     * }
     */
    /**
     * @param  array<int|string, array<string, mixed>>|null  $rowCorrections
     * @param  list<int>|null  $excludedRowNumbers
     */
    private function processStoredFile(
        int $tenantId,
        int $institutionDepartmentId,
        string $fullPath,
        string $originalFilename,
        ?array $rowCorrections = null,
        ?array $excludedRowNumbers = null,
    ): array {
        $user = Auth::user();
        $userId = $user instanceof User ? $user->id : null;

        $analysis = $this->analyseFile(
            $fullPath,
            $tenantId,
            $institutionDepartmentId,
            dryRun: true,
            rowCorrections: $rowCorrections,
            excludedRowNumbers: $excludedRowNumbers,
        );

        if ($analysis['summary']['failed'] > 0) {
            throw ValidationException::withMessages([
                'preview_token' => [__('syllabus.import_preview_cannot_confirm')],
            ]);
        }

        $importableRows = $analysis['summary']['syllabusCreates']
            + $analysis['summary']['syllabusUpdates']
            + $analysis['summary']['moduleCreates']
            + $analysis['summary']['moduleUpdates'];

        if ($importableRows === 0) {
            throw ValidationException::withMessages([
                'preview_token' => [__('syllabus.import_no_rows')],
            ]);
        }

        $ingestRun = IngestRun::query()->create([
            'importer' => CourseSyllabusImporter::IMPORTER_NAME,
            'user_id' => $userId,
            'status' => IngestStatus::PROCESSING,
            'original_filename' => $originalFilename,
            'processed_filepath' => $fullPath,
        ]);

        try {
            $parsedRows = $this->prepareParsedRows($fullPath, $rowCorrections, $excludedRowNumbers);
            $syllabusImporter = new CourseSyllabusImporter($tenantId, $institutionDepartmentId, fromFilesystem: false);
            $moduleImporter = new CourseSyllabusModuleImporter($tenantId, $institutionDepartmentId, fromFilesystem: false);

            /** @var RowProcessor $rowProcessor */
            $rowProcessor = app(RowProcessor::class);

            $syllabusChunk = $this->buildChunk($parsedRows);
            $syllabusResults = $rowProcessor->processChunk(
                $ingestRun,
                $syllabusImporter->getConfig(),
                $syllabusChunk,
                false,
            );

            $moduleChunk = $this->buildChunk($parsedRows);
            $moduleResults = $rowProcessor->processChunk(
                $ingestRun,
                $moduleImporter->getConfig(),
                $moduleChunk,
                false,
            );

            $rowsSucceeded = $syllabusResults['successful'] + $moduleResults['successful'];
            $rowsFailed = $syllabusResults['failed'] + $moduleResults['failed'];
            $rowsSkipped = $analysis['summary']['moduleSkips'];
            $rowsTotal = $analysis['summary']['total'];

            $ingestRun->update([
                'status' => $rowsFailed > 0 ? IngestStatus::COMPLETED_WITH_ERRORS : IngestStatus::COMPLETED,
                'total_rows' => $rowsTotal,
                'processed_rows' => $rowsTotal,
                'successful_rows' => $rowsSucceeded,
                'failed_rows' => $rowsFailed,
                'completed_at' => now(),
                'summary' => [
                    'skipped' => $rowsSkipped,
                ],
            ]);

            $importLog = CourseSyllabusImportLog::query()->create([
                'tenant_id' => $tenantId,
                'institution_department_id' => $institutionDepartmentId,
                'user_id' => $userId,
                'ingest_run_id' => $ingestRun->id,
                'original_filename' => $originalFilename,
                'rows_total' => $rowsTotal,
                'rows_succeeded' => $rowsSucceeded,
                'rows_failed' => $rowsFailed,
                'rows_skipped' => $rowsSkipped,
                'created_at' => now(),
            ]);

            return [
                'ingestRunId' => $ingestRun->id,
                'importLogId' => $importLog->id,
                'rowsTotal' => $rowsTotal,
                'rowsSucceeded' => $rowsSucceeded,
                'rowsFailed' => $rowsFailed,
                'rowsSkipped' => $rowsSkipped,
            ];
        } catch (Throwable $exception) {
            $ingestRun->update([
                'status' => IngestStatus::FAILED,
                'completed_at' => now(),
            ]);

            throw $exception;
        }
    }

    /**
     * @return array{
     *     summary: array{total: int, syllabusCreates: int, syllabusUpdates: int, syllabusSkips: int, syllabusFails: int, moduleCreates: int, moduleUpdates: int, moduleSkips: int, moduleFails: int, failed: int},
     *     rows: list<array<string, mixed>>,
     * }
     */
    /**
     * @param  array<int|string, array<string, mixed>>|null  $rowCorrections
     * @param  list<int>|null  $excludedRowNumbers
     */
    private function analyseFile(
        string $fullPath,
        int $tenantId,
        int $institutionDepartmentId,
        bool $dryRun,
        ?array $rowCorrections = null,
        ?array $excludedRowNumbers = null,
    ): array {
        $parsedRows = $this->prepareParsedRows($fullPath, $rowCorrections, $excludedRowNumbers);

        if ($parsedRows === []) {
            throw ValidationException::withMessages([
                'file' => [__('syllabus.import_no_rows')],
            ]);
        }

        $syllabusImporter = new CourseSyllabusImporter($tenantId, $institutionDepartmentId, fromFilesystem: false);
        $moduleImporter = new CourseSyllabusModuleImporter($tenantId, $institutionDepartmentId, fromFilesystem: false);

        /** @var RowProcessor $rowProcessor */
        $rowProcessor = app(RowProcessor::class);

        $summary = [
            'total' => count($parsedRows),
            'syllabusCreates' => 0,
            'syllabusUpdates' => 0,
            'syllabusSkips' => 0,
            'syllabusFails' => 0,
            'moduleCreates' => 0,
            'moduleUpdates' => 0,
            'moduleSkips' => 0,
            'moduleFails' => 0,
            'failed' => 0,
        ];

        $rows = [];

        foreach ($parsedRows as $parsedRow) {
            $rowData = $parsedRow['data'];
            $syllabusAnalysis = $this->analyseSyllabusRow(
                $rowProcessor,
                $syllabusImporter,
                $parsedRow,
                $tenantId,
                $dryRun,
            );
            $moduleAnalysis = $this->analyseModuleRow(
                $rowProcessor,
                $moduleImporter,
                $parsedRow,
                $tenantId,
                $institutionDepartmentId,
                $dryRun,
            );

            $summary['syllabusCreates'] += $syllabusAnalysis['action'] === 'create' ? 1 : 0;
            $summary['syllabusUpdates'] += $syllabusAnalysis['action'] === 'update' ? 1 : 0;
            $summary['syllabusSkips'] += $syllabusAnalysis['action'] === 'skip' ? 1 : 0;
            $summary['syllabusFails'] += $syllabusAnalysis['action'] === 'fail' ? 1 : 0;
            $summary['moduleCreates'] += $moduleAnalysis['action'] === 'create' ? 1 : 0;
            $summary['moduleUpdates'] += $moduleAnalysis['action'] === 'update' ? 1 : 0;
            $summary['moduleSkips'] += $moduleAnalysis['action'] === 'skip' ? 1 : 0;
            $summary['moduleFails'] += $moduleAnalysis['action'] === 'fail' ? 1 : 0;

            if ($syllabusAnalysis['action'] === 'fail' || $moduleAnalysis['action'] === 'fail') {
                $summary['failed']++;
            }

            $rows[] = [
                'rowNumber' => $parsedRow['number'],
                'level' => trim((string) ($rowData['LEVEL'] ?? '')),
                'courseTitle' => trim((string) ($rowData['COURSE_TITLE'] ?? '')),
                'courseCode' => trim((string) ($rowData['COURSE_CODE'] ?? '')),
                'semester' => trim((string) ($rowData['SEMESTER'] ?? '')),
                'moduleTitle' => trim((string) ($rowData['MODULE_TITLE'] ?? '')),
                'moduleCode' => trim((string) ($rowData['MODULE_CODE'] ?? '')),
                'syllabusExists' => CourseSyllabus::query()
                    ->where('tenant_id', $tenantId)
                    ->where('code', trim((string) ($rowData['COURSE_CODE'] ?? '')))
                    ->exists(),
                'moduleExists' => trim((string) ($rowData['MODULE_CODE'] ?? '')) !== ''
                    && CourseSyllabusModule::query()
                        ->where('tenant_id', $tenantId)
                        ->where('code', trim((string) ($rowData['MODULE_CODE'] ?? '')))
                        ->exists(),
                'syllabusAction' => $syllabusAnalysis['action'],
                'moduleAction' => $moduleAnalysis['action'],
                'syllabusErrors' => $syllabusAnalysis['errors'],
                'moduleErrors' => $moduleAnalysis['errors'],
            ];
        }

        return [
            'summary' => $summary,
            'rows' => $rows,
        ];
    }

    /**
     * @param  array{number: int, data: array<string, mixed>}  $parsedRow
     * @return array{action: string, errors: list<string>}
     */
    private function analyseSyllabusRow(
        RowProcessor $rowProcessor,
        CourseSyllabusImporter $importer,
        array $parsedRow,
        int $tenantId,
        bool $dryRun,
    ): array {
        $rowData = $parsedRow['data'];
        $courseCode = trim((string) ($rowData['COURSE_CODE'] ?? ''));

        if ($courseCode === '' || trim((string) ($rowData['LEVEL'] ?? '')) === '' || trim((string) ($rowData['COURSE_TITLE'] ?? '')) === '') {
            return [
                'action' => 'fail',
                'errors' => [__('syllabus.import_syllabus_required_fields')],
            ];
        }

        $exists = CourseSyllabus::query()
            ->where('tenant_id', $tenantId)
            ->where('code', $courseCode)
            ->exists();

        $analysis = $this->dryRunRow($rowProcessor, $importer->getConfig(), $parsedRow, $dryRun);

        if ($analysis['action'] === 'fail') {
            return $analysis;
        }

        return [
            'action' => $exists ? 'update' : 'create',
            'errors' => [],
        ];
    }

    /**
     * @param  array{number: int, data: array<string, mixed>}  $parsedRow
     * @return array{action: string, errors: list<string>}
     */
    private function analyseModuleRow(
        RowProcessor $rowProcessor,
        CourseSyllabusModuleImporter $importer,
        array $parsedRow,
        int $tenantId,
        int $institutionDepartmentId,
        bool $dryRun,
    ): array {
        $rowData = $parsedRow['data'];
        $moduleCode = trim((string) ($rowData['MODULE_CODE'] ?? ''));
        $moduleTitle = trim((string) ($rowData['MODULE_TITLE'] ?? ''));
        $courseCode = trim((string) ($rowData['COURSE_CODE'] ?? ''));
        $semester = trim((string) ($rowData['SEMESTER'] ?? ''));

        if ($moduleCode === '' && $moduleTitle === '') {
            return ['action' => 'skip', 'errors' => []];
        }

        if ($moduleCode === '' || $moduleTitle === '' || $courseCode === '' || $semester === '') {
            return [
                'action' => 'fail',
                'errors' => [__('syllabus.import_module_required_fields')],
            ];
        }

        $moduleExists = CourseSyllabusModule::query()
            ->where('tenant_id', $tenantId)
            ->where('code', $moduleCode)
            ->exists();

        $courseSyllabusId = CourseSyllabus::query()
            ->where('tenant_id', $tenantId)
            ->where('code', $courseCode)
            ->value('id');

        if ($courseSyllabusId === null) {
            try {
                app(ResolveAcademicYearOptionFromImport::class)->resolve(
                    $semester,
                    null,
                    $institutionDepartmentId,
                    (string) ($rowData['LEVEL'] ?? ''),
                );
            } catch (Throwable $exception) {
                return [
                    'action' => 'fail',
                    'errors' => [$exception->getMessage()],
                ];
            }

            return ['action' => 'create', 'errors' => []];
        }

        $analysis = $this->dryRunRow($rowProcessor, $importer->getConfig(), $parsedRow, $dryRun);

        if ($analysis['action'] === 'fail') {
            return $analysis;
        }

        return [
            'action' => $moduleExists ? 'update' : 'create',
            'errors' => [],
        ];
    }

    /**
     * @param  array{number: int, data: array<string, mixed>}  $parsedRow
     * @return array{action: string, errors: list<string>}
     */
    private function dryRunRow(RowProcessor $rowProcessor, $config, array $parsedRow, bool $dryRun): array
    {
        $ingestRun = IngestRun::query()->create([
            'importer' => 'preview',
            'status' => IngestStatus::PROCESSING,
        ]);

        try {
            $results = $rowProcessor->processChunk($ingestRun, $config, [$parsedRow], $dryRun);

            if (($results['failed'] ?? 0) > 0) {
                $error = $ingestRun->rows()->where('status', 'failed')->value('errors');
                $message = is_string($error) ? json_decode($error, true)['message'] ?? $error : __('syllabus.import_row_failed');

                return [
                    'action' => 'fail',
                    'errors' => [is_string($message) ? $message : __('syllabus.import_row_failed')],
                ];
            }

            return ['action' => 'create', 'errors' => []];
        } catch (Throwable $exception) {
            return [
                'action' => 'fail',
                'errors' => [$exception->getMessage()],
            ];
        } finally {
            $ingestRun->delete();
        }
    }

    /**
     * @param  list<array{number: int, data: array<string, mixed>}>  $parsedRows
     * @return list<array{number: int, data: array<string, mixed>}>
     */
    private function buildChunk(array $parsedRows): array
    {
        return array_values($parsedRows);
    }

    /**
     * @param  array<int|string, array<string, mixed>>|null  $rowCorrections
     * @param  list<int>|null  $excludedRowNumbers
     * @return list<array{number: int, data: array<string, mixed>}>
     */
    private function prepareParsedRows(
        string $fullPath,
        ?array $rowCorrections = null,
        ?array $excludedRowNumbers = null,
    ): array {
        $excluded = $this->normalizeExcludedRowNumbers($excludedRowNumbers);
        $parsedRows = [];

        foreach ($this->parseRows($fullPath) as $parsedRow) {
            if (in_array($parsedRow['number'], $excluded, true)) {
                continue;
            }

            $corrections = $this->correctionsForRow($rowCorrections, $parsedRow['number']);
            $parsedRows[] = $this->applyCorrectionsToParsedRow($parsedRow, $corrections);
        }

        return $parsedRows;
    }

    /**
     * @param  list<int>|null  $excludedRowNumbers
     * @return list<int>
     */
    private function normalizeExcludedRowNumbers(?array $excludedRowNumbers): array
    {
        if ($excludedRowNumbers === null) {
            return [];
        }

        return array_values(array_unique(array_map('intval', $excludedRowNumbers)));
    }

    /**
     * @param  array<int|string, array<string, mixed>>|null  $rowCorrections
     * @return array<string, mixed>|null
     */
    private function correctionsForRow(?array $rowCorrections, int $rowNumber): ?array
    {
        if ($rowCorrections === null) {
            return null;
        }

        $corrections = $rowCorrections[$rowNumber] ?? $rowCorrections[(string) $rowNumber] ?? null;

        return is_array($corrections) ? $corrections : null;
    }

    /**
     * @param  array{number: int, data: array<string, mixed>}  $parsedRow
     * @param  array<string, mixed>|null  $corrections
     * @return array{number: int, data: array<string, mixed>}
     */
    private function applyCorrectionsToParsedRow(array $parsedRow, ?array $corrections): array
    {
        if ($corrections === null) {
            return $parsedRow;
        }

        $fieldMap = [
            'level' => 'LEVEL',
            'courseTitle' => 'COURSE_TITLE',
            'courseCode' => 'COURSE_CODE',
            'semester' => 'SEMESTER',
            'moduleTitle' => 'MODULE_TITLE',
            'moduleCode' => 'MODULE_CODE',
        ];

        foreach ($fieldMap as $correctionKey => $columnKey) {
            if (! array_key_exists($correctionKey, $corrections)) {
                continue;
            }

            $parsedRow['data'][$columnKey] = trim((string) $corrections[$correctionKey]);
        }

        return $parsedRow;
    }

    /**
     * @return list<array{number: int, data: array<string, mixed>}>
     */
    private function parseRows(string $fullPath): array
    {
        $rawRows = $this->readRawRows($fullPath);
        $headerRowIndex = $this->detectHeaderRowIndex($rawRows);

        if ($headerRowIndex === null) {
            throw ValidationException::withMessages([
                'file' => [__('syllabus.import_invalid_format')],
            ]);
        }

        $parsedRows = [];
        $rowNumber = 0;

        for ($index = $headerRowIndex + 1; $index < count($rawRows); $index++) {
            $rawRow = $rawRows[$index];

            if (! $this->rowHasContent($rawRow) || CourseSyllabusImporter::isHeaderRow($rawRow)) {
                continue;
            }

            $rowNumber++;
            $parsedRows[] = [
                'number' => $rowNumber,
                'data' => CourseSyllabusImporter::rowToAssociative($rawRow),
            ];
        }

        return $parsedRows;
    }

    /**
     * @return list<list<mixed>>
     */
    private function readRawRows(string $fullPath): array
    {
        $rows = [];

        SimpleExcelReader::create($fullPath)->noHeaderRow()->getRows()->each(function (array $row) use (&$rows): void {
            $rows[] = array_values($row);
        });

        return $rows;
    }

    /**
     * @param  list<list<mixed>>  $rawRows
     */
    private function detectHeaderRowIndex(array $rawRows): ?int
    {
        $limit = min(count($rawRows), 50);

        for ($index = 0; $index < $limit; $index++) {
            if (CourseSyllabusImporter::isHeaderRow($rawRows[$index])) {
                return $index;
            }
        }

        return null;
    }

    /**
     * @param  list<mixed>  $row
     */
    private function rowHasContent(array $row): bool
    {
        foreach ($row as $value) {
            if (trim((string) $value) !== '') {
                return true;
            }
        }

        return false;
    }

    /**
     * @return array{tenantId: int, institutionDepartmentId: int}
     */
    private function resolveDepartmentContext(int $institutionDepartmentId): array
    {
        $department = InstitutionDepartment::query()->findOrFail($institutionDepartmentId);

        return [
            'tenantId' => (int) $department->tenant_id,
            'institutionDepartmentId' => (int) $department->id,
        ];
    }
}
