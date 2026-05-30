<?php

namespace App\Services\AcademicCalendars;

use App\Importers\AcademicCalendars\CourseWorkMarkImporter;
use App\Models\AcademicCalendars\CourseWorkImportLog;
use App\Models\AcademicCalendars\CourseWorkMark;
use App\Models\Users\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use LaravelIngest\Enums\IngestStatus;
use LaravelIngest\Models\IngestRow;
use LaravelIngest\Models\IngestRun;
use Spatie\SimpleExcel\SimpleExcelReader;
use Throwable;

class CourseWorkImportService
{
    private const string PREVIEW_CACHE_PREFIX = 'course-work-import-preview:';

    private const int PREVIEW_TTL_MINUTES = 30;

    public function __construct(
        private readonly CourseWorkMarkService $markService,
    ) {}

    /**
     * @return array{
     *     previewToken: string,
     *     fileName: string,
     *     summary: array<string, int>,
     *     rows: list<array<string, mixed>>,
     * }
     */
    public function preview(int $classConfigId, int $moduleId, UploadedFile $file): array
    {
        $this->markService->assertClassConfigExists($classConfigId);
        $importer = new CourseWorkMarkImporter($classConfigId, $moduleId);

        $storedPath = $file->store('course-work-imports/previews', 'ingest');
        $fullPath = Storage::disk('ingest')->path($storedPath);

        $analysis = $this->analyseFile($fullPath, $importer, dryRun: true);

        $user = Auth::user();
        $previewToken = Str::random(40);

        Cache::put(
            self::PREVIEW_CACHE_PREFIX.$previewToken,
            [
                'path' => $storedPath,
                'class_config_id' => $classConfigId,
                'module_id' => $moduleId,
                'user_id' => $user instanceof User ? $user->id : null,
                'original_filename' => $file->getClientOriginalName(),
            ],
            now()->addMinutes(self::PREVIEW_TTL_MINUTES),
        );

        return [
            'previewToken' => $previewToken,
            'fileName' => $file->getClientOriginalName(),
            'summary' => $analysis['summary'],
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
    public function processFromPreview(int $classConfigId, int $moduleId, string $previewToken): array
    {
        $preview = Cache::get(self::PREVIEW_CACHE_PREFIX.$previewToken);

        if (! is_array($preview)) {
            throw ValidationException::withMessages([
                'preview_token' => [__('academic_calendar.course_work_import_preview_expired')],
            ]);
        }

        $user = Auth::user();
        $userId = $user instanceof User ? $user->id : null;

        if ($userId === null || (int) ($preview['user_id'] ?? 0) !== $userId) {
            throw ValidationException::withMessages([
                'preview_token' => [__('academic_calendar.course_work_import_preview_expired')],
            ]);
        }

        if ((int) ($preview['class_config_id'] ?? 0) !== $classConfigId
            || (int) ($preview['module_id'] ?? 0) !== $moduleId) {
            throw ValidationException::withMessages([
                'preview_token' => [__('academic_calendar.course_work_import_preview_mismatch')],
            ]);
        }

        $storedPath = (string) ($preview['path'] ?? '');
        $fullPath = Storage::disk('ingest')->path($storedPath);

        if (! Storage::disk('ingest')->exists($storedPath)) {
            throw ValidationException::withMessages([
                'preview_token' => [__('academic_calendar.course_work_import_preview_expired')],
            ]);
        }

        try {
            return $this->processStoredFile(
                $classConfigId,
                $moduleId,
                $fullPath,
                (string) ($preview['original_filename'] ?? 'import.xlsx'),
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
    private function processStoredFile(
        int $classConfigId,
        int $moduleId,
        string $fullPath,
        string $originalFilename,
    ): array {
        $classConfig = $this->markService->assertClassConfigExists($classConfigId);
        $importer = new CourseWorkMarkImporter($classConfigId, $moduleId);

        $user = Auth::user();
        $userId = $user instanceof User ? $user->id : null;

        $ingestRun = IngestRun::query()->create([
            'importer' => CourseWorkMarkImporter::IMPORTER_NAME,
            'user_id' => $userId,
            'status' => IngestStatus::PROCESSING,
            'original_filename' => $originalFilename,
            'processed_filepath' => $fullPath,
        ]);

        try {
            $analysis = $this->analyseFile($fullPath, $importer, dryRun: false);

            if ($analysis['summary']['succeeded'] === 0) {
                throw ValidationException::withMessages([
                    'preview_token' => [__('academic_calendar.course_work_import_no_marks')],
                ]);
            }

            $rowsSucceeded = 0;
            $rowsFailed = 0;

            foreach ($analysis['rows'] as $rowResult) {
                if ($rowResult['status'] === 'skipped' || $rowResult['status'] === 'failed') {
                    $this->recordIngestRow(
                        $ingestRun,
                        (int) $rowResult['rowNumber'],
                        $rowResult['status'],
                        (array) ($rowResult['raw'] ?? []),
                        $rowResult['errors'] ?? null,
                    );

                    continue;
                }

                try {
                    /** @var array{studentEnrolmentId: int, courseSyllabusModuleId: int, assessmentTypeId: int, mark: int|null, remark: string|null} $payload */
                    $payload = $rowResult['payload'];
                    $this->markService->upsert($payload, classConfigId: $classConfigId);
                    $rowsSucceeded++;

                    $this->recordIngestRow(
                        $ingestRun,
                        (int) $rowResult['rowNumber'],
                        'success',
                        (array) ($rowResult['raw'] ?? []),
                        null,
                    );
                } catch (Throwable $exception) {
                    $rowsFailed++;

                    $errors = $exception instanceof ValidationException
                        ? $exception->errors()
                        : ['import' => [$exception->getMessage()]];

                    $this->recordIngestRow(
                        $ingestRun,
                        (int) $rowResult['rowNumber'],
                        'failed',
                        (array) ($rowResult['raw'] ?? []),
                        $errors,
                    );
                }
            }

            $summary = $analysis['summary'];
            $rowsSkipped = $summary['skipped'];

            $ingestRun->update([
                'status' => $rowsFailed > 0 ? IngestStatus::COMPLETED_WITH_ERRORS : IngestStatus::COMPLETED,
                'total_rows' => $summary['total'],
                'processed_rows' => $summary['total'],
                'successful_rows' => $rowsSucceeded,
                'failed_rows' => $rowsFailed,
                'completed_at' => now(),
                'summary' => [
                    'skipped' => $rowsSkipped,
                ],
            ]);

            $importLog = CourseWorkImportLog::query()->create([
                'class_config_id' => $classConfigId,
                'course_syllabus_module_id' => $moduleId,
                'user_id' => $userId,
                'ingest_run_id' => $ingestRun->id,
                'original_filename' => $originalFilename,
                'rows_total' => $summary['total'],
                'rows_succeeded' => $rowsSucceeded,
                'rows_failed' => $rowsFailed,
                'rows_skipped' => $rowsSkipped,
                'created_at' => now(),
            ]);

            return [
                'ingestRunId' => $ingestRun->id,
                'importLogId' => $importLog->id,
                'rowsTotal' => $summary['total'],
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
     *     summary: array{total: int, succeeded: int, failed: int, skipped: int, creates: int, updates: int},
     *     rows: list<array<string, mixed>>,
     * }
     */
    private function analyseFile(string $fullPath, CourseWorkMarkImporter $importer, bool $dryRun): array
    {
        $reader = $this->readerForFile($fullPath);

        $rows = [];
        $summary = [
            'total' => 0,
            'succeeded' => 0,
            'failed' => 0,
            'skipped' => 0,
            'creates' => 0,
            'updates' => 0,
        ];

        /** @var array<string, true> $seenMarkKeys */
        $seenMarkKeys = [];
        $rowNumber = 0;

        foreach ($reader->getRows() as $row) {
            if (! $this->rowHasContent($row)) {
                continue;
            }

            $rowNumber++;
            $summary['total']++;

            $normalized = $importer->normalizeRow($row);
            $display = $this->displayRow($normalized);

            if ($importer->isEmptyRow($row)) {
                $summary['skipped']++;
                $rows[] = [
                    'rowNumber' => $rowNumber,
                    'status' => 'skipped',
                    'action' => 'skip_empty',
                    'raw' => $row,
                    'errors' => null,
                    ...$display,
                ];

                continue;
            }

            try {
                $payload = $importer->extractPayload($row);
                $markKey = CourseWorkMarkImporter::markKeyFromPayload($payload);

                if (isset($seenMarkKeys[$markKey])) {
                    $summary['skipped']++;
                    $rows[] = [
                        'rowNumber' => $rowNumber,
                        'status' => 'skipped',
                        'action' => 'skip_duplicate',
                        'raw' => $row,
                        'errors' => [
                            'import' => [__('academic_calendar.course_work_import_duplicate_row')],
                        ],
                        ...$display,
                        'mark' => $payload['mark'],
                        'remark' => $payload['remark'],
                    ];

                    continue;
                }

                if (! $dryRun) {
                    $this->assertRowPermissions($payload);
                } else {
                    $this->assertRowPermissionsForPreview($payload);
                }

                $existing = CourseWorkMark::query()
                    ->withTrashed()
                    ->where('student_enrolment_id', $payload['studentEnrolmentId'])
                    ->where('course_syllabus_module_id', $payload['courseSyllabusModuleId'])
                    ->where('assessment_type_id', $payload['assessmentTypeId'])
                    ->first();

                $action = ($existing !== null && ! $existing->trashed()) ? 'update' : 'create';

                if ($action === 'create') {
                    $summary['creates']++;
                } else {
                    $summary['updates']++;
                }

                $seenMarkKeys[$markKey] = true;
                $summary['succeeded']++;

                $rows[] = [
                    'rowNumber' => $rowNumber,
                    'status' => 'ready',
                    'action' => $action,
                    'payload' => $payload,
                    'raw' => $row,
                    'errors' => null,
                    ...$display,
                    'mark' => $payload['mark'],
                    'remark' => $payload['remark'],
                ];
            } catch (Throwable $exception) {
                $summary['failed']++;
                $errors = $exception instanceof ValidationException
                    ? $exception->errors()
                    : ['import' => [$exception->getMessage()]];

                $rows[] = [
                    'rowNumber' => $rowNumber,
                    'status' => 'failed',
                    'action' => 'fail',
                    'raw' => $row,
                    'errors' => $errors,
                    ...$display,
                ];
            }
        }

        if ($summary['total'] === 0) {
            throw ValidationException::withMessages([
                'file' => [__('academic_calendar.course_work_import_no_rows')],
            ]);
        }

        if ($summary['succeeded'] === 0 && $summary['failed'] === 0) {
            throw ValidationException::withMessages([
                'file' => [__('academic_calendar.course_work_import_no_marks')],
            ]);
        }

        return [
            'summary' => $summary,
            'rows' => $rows,
        ];
    }

    private function readerForFile(string $fullPath): SimpleExcelReader
    {
        $headerRowIndex = $this->detectHeaderRowIndex($fullPath);

        if ($headerRowIndex === null) {
            throw ValidationException::withMessages([
                'file' => [__('academic_calendar.course_work_import_invalid_format')],
            ]);
        }

        return SimpleExcelReader::create($fullPath)->headerOnRow($headerRowIndex);
    }

    private function detectHeaderRowIndex(string $fullPath): ?int
    {
        $reader = SimpleExcelReader::create($fullPath)->noHeaderRow();
        $index = 0;

        foreach ($reader->getRows() as $row) {
            $values = array_map(
                static fn ($value): string => strtoupper(trim((string) $value)),
                array_values($row),
            );

            if (in_array('STUDENT_ENROLMENT_ID', $values, true)) {
                return $index;
            }

            $index++;

            if ($index > 50) {
                break;
            }
        }

        return null;
    }

    /**
     * @param  array<string, mixed>  $normalized
     * @return array{studentName: string|null, studentNumber: string|null, assessmentName: string|null}
     */
    private function displayRow(array $normalized): array
    {
        return [
            'studentName' => isset($normalized['STUDENT_NAME']) ? (string) $normalized['STUDENT_NAME'] : null,
            'studentNumber' => isset($normalized['STUDENT_NUMBER']) ? (string) $normalized['STUDENT_NUMBER'] : null,
            'assessmentName' => isset($normalized['ASSESSMENT_NAME']) ? (string) $normalized['ASSESSMENT_NAME'] : null,
        ];
    }

    /**
     * @param  array<string, mixed>  $row
     */
    private function rowHasContent(array $row): bool
    {
        return collect($row)->contains(
            static fn ($value): bool => $value !== null && trim((string) $value) !== '',
        );
    }

    /**
     * @param  array{studentEnrolmentId: int, courseSyllabusModuleId: int, assessmentTypeId: int, mark: int|null, remark: string|null}  $payload
     */
    private function assertRowPermissions(array $payload): void
    {
        $this->assertRowPermissionsForPreview($payload);
    }

    /**
     * @param  array{studentEnrolmentId: int, courseSyllabusModuleId: int, assessmentTypeId: int, mark: int|null, remark: string|null}  $payload
     */
    private function assertRowPermissionsForPreview(array $payload): void
    {
        $user = Auth::user();
        if (! $user instanceof User) {
            throw ValidationException::withMessages([
                'auth' => [__('academic_calendar.course_work_import_auth_required')],
            ]);
        }

        $existing = CourseWorkMark::query()
            ->withTrashed()
            ->where('student_enrolment_id', $payload['studentEnrolmentId'])
            ->where('course_syllabus_module_id', $payload['courseSyllabusModuleId'])
            ->where('assessment_type_id', $payload['assessmentTypeId'])
            ->first();

        if ($existing !== null && ! $existing->trashed() && ! $user->can('update', CourseWorkMark::class)) {
            throw ValidationException::withMessages([
                'permission' => [__('academic_calendar.course_work_import_update_denied')],
            ]);
        }

        if (($existing === null || $existing->trashed()) && ! $user->can('create', CourseWorkMark::class)) {
            throw ValidationException::withMessages([
                'permission' => [__('academic_calendar.course_work_import_create_denied')],
            ]);
        }
    }

    /**
     * @param  array<string, mixed>  $row
     * @param  array<string, list<string>>|null  $errors
     */
    private function recordIngestRow(
        IngestRun $ingestRun,
        int $rowNumber,
        string $status,
        array $row,
        ?array $errors,
    ): void {
        if (! config('ingest.log_rows', true)) {
            return;
        }

        IngestRow::query()->create([
            'ingest_run_id' => $ingestRun->id,
            'row_number' => $rowNumber,
            'status' => $status,
            'data' => $row,
            'errors' => $errors,
        ]);
    }
}
