<?php

namespace App\Services\AcademicCalendars;

use App\Importers\AcademicCalendars\CourseWorkMarkImporter;
use App\Models\AcademicCalendars\CourseWorkImportLog;
use App\Models\AcademicCalendars\CourseWorkMark;
use App\Models\Institution\Syllabus\CourseSyllabusModule;
use App\Models\Students\StudentEnrolment;
use App\Models\Users\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;
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
     *     layout: string,
     *     assessmentColumns: list<array{id: int, name: string, weightPercent: int|null}>,
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

        $analysis = $this->analyseFile($fullPath, $importer, $moduleId, dryRun: true);

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
                'layout' => $analysis['layout'],
            ],
            now()->addMinutes(self::PREVIEW_TTL_MINUTES),
        );

        return [
            'previewToken' => $previewToken,
            'fileName' => $file->getClientOriginalName(),
            'layout' => $analysis['layout'],
            'assessmentColumns' => $analysis['assessmentColumns'],
            'summary' => $analysis['summary'],
            'rows' => $analysis['layout'] === 'mark_only'
                ? $this->formatMarkOnlyPreviewRows($analysis)
                : $this->formatWidePreviewRows($analysis),
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
            $analysis = $this->analyseFile($fullPath, $importer, $moduleId, dryRun: false);

            if ($analysis['summary']['succeeded'] === 0) {
                throw ValidationException::withMessages([
                    'preview_token' => [__('academic_calendar.course_work_import_no_marks')],
                ]);
            }

            $rowsSucceeded = 0;
            $rowsFailed = 0;

            foreach ($analysis['markOperations'] as $operation) {
                if ($operation['status'] === 'skipped' || $operation['status'] === 'failed') {
                    $this->recordIngestRow(
                        $ingestRun,
                        (int) $operation['rowNumber'],
                        $operation['status'],
                        (array) ($operation['raw'] ?? []),
                        $operation['errors'] ?? null,
                    );

                    continue;
                }

                try {
                    /** @var array{studentEnrolmentId: int, courseSyllabusModuleId: int, assessmentTypeId?: int|null, mark: int|null, remark: string|null} $payload */
                    $payload = $operation['payload'];
                    $this->markService->upsert($payload, classConfigId: $classConfigId);
                    $rowsSucceeded++;

                    $this->recordIngestRow(
                        $ingestRun,
                        (int) $operation['rowNumber'],
                        'success',
                        (array) ($operation['raw'] ?? []),
                        null,
                    );
                } catch (Throwable $exception) {
                    $rowsFailed++;

                    $errors = $exception instanceof ValidationException
                        ? $exception->errors()
                        : ['import' => [$exception->getMessage()]];

                    $this->recordIngestRow(
                        $ingestRun,
                        (int) $operation['rowNumber'],
                        'failed',
                        (array) ($operation['raw'] ?? []),
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
     *     assessmentColumns: list<array{id: int, name: string, weightPercent: int|null}>,
     *     markOperations: list<array<string, mixed>>,
     *     studentRows: list<array<string, mixed>>,
     * }
     */
    private function analyseFile(string $fullPath, CourseWorkMarkImporter $importer, int $moduleId, bool $dryRun): array
    {
        $module = CourseSyllabusModule::query()->findOrFail($moduleId);
        $rawRows = $this->readRawRows($fullPath);
        $headerRowIndex = $this->detectHeaderRowIndex($rawRows);

        if ($headerRowIndex === null) {
            throw ValidationException::withMessages([
                'file' => [__('academic_calendar.course_work_import_invalid_format')],
            ]);
        }

        $headerRow = $rawRows[$headerRowIndex];
        $isMarkOnlyHeader = CourseWorkMarkImporter::isMarkOnlyFormatHeader($headerRow);
        $isWideHeader = CourseWorkMarkImporter::isWideFormatHeader($headerRow);

        if ($module->capture_mark_only) {
            if (! $isMarkOnlyHeader) {
                throw ValidationException::withMessages([
                    'file' => [__('academic_calendar.course_work_import_layout_mismatch')],
                ]);
            }

            return $this->analyseMarkOnlyFile($rawRows, $headerRowIndex, $importer, $dryRun);
        }

        if (! $isWideHeader || $isMarkOnlyHeader) {
            throw ValidationException::withMessages([
                'file' => [__('academic_calendar.course_work_import_layout_mismatch')],
            ]);
        }

        return $this->analyseWideFile($rawRows, $headerRowIndex, $importer, $dryRun);
    }

    /**
     * @param  list<array<int|string, mixed>>  $rawRows
     * @return array{
     *     layout: string,
     *     summary: array{total: int, succeeded: int, failed: int, skipped: int, creates: int, updates: int},
     *     assessmentColumns: list<array{id: int, name: string, weightPercent: int|null}>,
     *     markOperations: list<array<string, mixed>>,
     *     studentRows: list<array<string, mixed>>,
     * }
     */
    private function analyseWideFile(array $rawRows, int $headerRowIndex, CourseWorkMarkImporter $importer, bool $dryRun): array
    {
        $headerRow = $rawRows[$headerRowIndex];

        $idRow = $rawRows[$headerRowIndex + 1] ?? [];
        $columnMap = CourseWorkMarkImporter::parseWideColumnMap($idRow);

        if ($columnMap === []) {
            throw ValidationException::withMessages([
                'file' => [__('academic_calendar.course_work_import_invalid_format')],
            ]);
        }

        $assessmentColumns = CourseWorkMarkImporter::assessmentColumnsFromHeader($headerRow, $columnMap);
        $classConfig = $this->markService->assertClassConfigExists($importer->classConfigId());
        $expectedModeOfStudyId = (int) $classConfig->mode_of_study_id;
        $moduleId = $importer->moduleId();

        $studentEnrolmentIds = $this->collectStudentEnrolmentIdsFromRows(
            $rawRows,
            $headerRowIndex,
        );

        /** @var Collection<string, Collection<int, CourseWorkMark>> $existingMarksByKey */
        $existingMarksByKey = $studentEnrolmentIds === []
            ? collect()
            : CourseWorkMark::query()
                ->withTrashed()
                ->where('course_syllabus_module_id', $moduleId)
                ->whereIn('student_enrolment_id', $studentEnrolmentIds)
                ->get()
                ->groupBy(fn (CourseWorkMark $mark): string => CourseWorkMarkImporter::markKey(
                    (int) $mark->student_enrolment_id,
                    (int) $mark->course_syllabus_module_id,
                    $mark->assessment_type_id !== null ? (int) $mark->assessment_type_id : null,
                ));

        /** @var array<int, int|null> $modeOfStudyByEnrolmentId */
        $modeOfStudyByEnrolmentId = $studentEnrolmentIds === []
            ? []
            : StudentEnrolment::query()
                ->whereIn('id', $studentEnrolmentIds)
                ->pluck('mode_of_study_id', 'id')
                ->map(fn ($modeId): ?int => $modeId !== null ? (int) $modeId : null)
                ->all();

        $markOperations = [];
        $studentRows = [];

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
        $studentRowNumber = 0;

        for ($index = $headerRowIndex + 2; $index < count($rawRows); $index++) {
            $studentRow = $rawRows[$index];

            if (! $this->rowHasContent($studentRow)) {
                continue;
            }

            $studentRowNumber++;
            $display = CourseWorkMarkImporter::displayFromWideRow($studentRow);
            $cellResults = [];

            if ($importer->isEmptyWideRow($studentRow, $columnMap)) {
                foreach ($columnMap as $assessmentTypeId) {
                    $summary['skipped']++;
                    $cellResults[$assessmentTypeId] = [
                        'mark' => null,
                        'action' => 'skip_empty',
                        'errors' => null,
                    ];
                }

                $studentRows[] = [
                    'rowNumber' => $studentRowNumber,
                    ...$display,
                    'className' => $display['className'],
                    'cellResults' => $cellResults,
                    'raw' => $studentRow,
                ];

                continue;
            }

            $rowValues = array_values($studentRow);
            $studentEnrolmentId = isset($rowValues[0]) && is_numeric($rowValues[0])
                ? (int) $rowValues[0]
                : 0;
            $studentModeOfStudyId = $modeOfStudyByEnrolmentId[$studentEnrolmentId] ?? null;

            if ($studentModeOfStudyId === null || $studentModeOfStudyId !== $expectedModeOfStudyId) {
                $modeMismatchErrors = [
                    'import' => [__('academic_calendar.course_work_import_mode_of_study_mismatch')],
                ];

                foreach ($columnMap as $assessmentTypeId) {
                    $summary['failed']++;
                    $cellResults[$assessmentTypeId] = [
                        'mark' => null,
                        'action' => 'fail',
                        'errors' => $modeMismatchErrors,
                    ];

                    $markOperations[] = [
                        'rowNumber' => $studentRowNumber,
                        'status' => 'failed',
                        'action' => 'fail',
                        'assessmentTypeId' => $assessmentTypeId,
                        'raw' => $studentRow,
                        'errors' => $modeMismatchErrors,
                        ...$display,
                    ];
                }

                $studentRows[] = [
                    'rowNumber' => $studentRowNumber,
                    ...$display,
                    'className' => $display['className'],
                    'cellResults' => $cellResults,
                    'raw' => $studentRow,
                ];

                continue;
            }

            foreach ($columnMap as $columnIndex => $assessmentTypeId) {
                $markValue = $rowValues[$columnIndex] ?? null;
                $markEmpty = $markValue === null || $markValue === '';

                if ($markEmpty) {
                    $markKey = CourseWorkMarkImporter::markKey($studentEnrolmentId, $moduleId, $assessmentTypeId);
                    $existing = $existingMarksByKey->get($markKey)?->first();

                    if ($existing !== null && ! $existing->trashed()) {
                        $summary['skipped']++;
                        $cellResults[$assessmentTypeId] = [
                            'mark' => $existing->mark !== null ? (int) $existing->mark : null,
                            'action' => 'skip_unchanged',
                            'errors' => null,
                        ];

                        continue;
                    }

                    $payload = [
                        'studentEnrolmentId' => $studentEnrolmentId,
                        'courseSyllabusModuleId' => $moduleId,
                        'assessmentTypeId' => $assessmentTypeId,
                        'mark' => 0,
                        'remark' => null,
                    ];
                } else {
                    try {
                        $payload = $importer->extractWideMarkPayload($studentRow, $assessmentTypeId, $markValue);
                    } catch (Throwable $exception) {
                        $summary['failed']++;
                        $errors = $exception instanceof ValidationException
                            ? $exception->errors()
                            : ['import' => [$exception->getMessage()]];

                        $cellResults[$assessmentTypeId] = [
                            'mark' => is_numeric($markValue) ? (int) $markValue : null,
                            'action' => 'fail',
                            'errors' => $errors,
                        ];

                        $markOperations[] = [
                            'rowNumber' => $studentRowNumber,
                            'status' => 'failed',
                            'action' => 'fail',
                            'assessmentTypeId' => $assessmentTypeId,
                            'raw' => $studentRow,
                            'errors' => $errors,
                            ...$display,
                        ];

                        continue;
                    }
                }

                $summary['total']++;

                try {
                    $markKey = CourseWorkMarkImporter::markKeyFromPayload($payload);

                    if (isset($seenMarkKeys[$markKey])) {
                        $summary['skipped']++;
                        $cellResults[$assessmentTypeId] = [
                            'mark' => $payload['mark'],
                            'action' => 'skip_duplicate',
                            'errors' => [
                                'import' => [__('academic_calendar.course_work_import_duplicate_row')],
                            ],
                        ];

                        $markOperations[] = [
                            'rowNumber' => $studentRowNumber,
                            'status' => 'skipped',
                            'action' => 'skip_duplicate',
                            'assessmentTypeId' => $assessmentTypeId,
                            'raw' => $studentRow,
                            'errors' => [
                                'import' => [__('academic_calendar.course_work_import_duplicate_row')],
                            ],
                            ...$display,
                            'mark' => $payload['mark'],
                        ];

                        continue;
                    }

                    if ($dryRun) {
                        $this->assertRowPermissionsForPreview($payload);
                    } else {
                        $this->assertRowPermissions($payload);
                    }

                    $existing = $existingMarksByKey->get($markKey)?->first();
                    $action = ($existing !== null && ! $existing->trashed()) ? 'update' : 'create';

                    if ($action === 'create') {
                        $summary['creates']++;
                    } else {
                        $summary['updates']++;
                    }

                    $seenMarkKeys[$markKey] = true;
                    $summary['succeeded']++;

                    $cellResults[$assessmentTypeId] = [
                        'mark' => $payload['mark'],
                        'action' => $action,
                        'errors' => null,
                    ];

                    $markOperations[] = [
                        'rowNumber' => $studentRowNumber,
                        'status' => 'ready',
                        'action' => $action,
                        'assessmentTypeId' => $assessmentTypeId,
                        'payload' => $payload,
                        'raw' => $studentRow,
                        'errors' => null,
                        ...$display,
                        'mark' => $payload['mark'],
                    ];
                } catch (Throwable $exception) {
                    $summary['failed']++;
                    $errors = $exception instanceof ValidationException
                        ? $exception->errors()
                        : ['import' => [$exception->getMessage()]];

                    $cellResults[$assessmentTypeId] = [
                        'mark' => $payload['mark'] ?? (is_numeric($markValue) ? (int) $markValue : null),
                        'action' => 'fail',
                        'errors' => $errors,
                    ];

                    $markOperations[] = [
                        'rowNumber' => $studentRowNumber,
                        'status' => 'failed',
                        'action' => 'fail',
                        'assessmentTypeId' => $assessmentTypeId,
                        'raw' => $studentRow,
                        'errors' => $errors,
                        ...$display,
                    ];
                }
            }

            $studentRows[] = [
                'rowNumber' => $studentRowNumber,
                ...$display,
                'className' => $display['className'],
                'cellResults' => $cellResults,
                'raw' => $studentRow,
            ];
        }

        if ($studentRowNumber === 0) {
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
            'layout' => 'wide',
            'summary' => $summary,
            'assessmentColumns' => $assessmentColumns,
            'markOperations' => $markOperations,
            'studentRows' => $studentRows,
        ];
    }

    /**
     * @param  list<array<int|string, mixed>>  $rawRows
     * @return array{
     *     layout: string,
     *     summary: array{total: int, succeeded: int, failed: int, skipped: int, creates: int, updates: int},
     *     assessmentColumns: list<array{id: int, name: string, weightPercent: int|null}>,
     *     markOperations: list<array<string, mixed>>,
     *     studentRows: list<array<string, mixed>>,
     * }
     */
    private function analyseMarkOnlyFile(
        array $rawRows,
        int $headerRowIndex,
        CourseWorkMarkImporter $importer,
        bool $dryRun,
    ): array {
        $classConfig = $this->markService->assertClassConfigExists($importer->classConfigId());
        $expectedModeOfStudyId = (int) $classConfig->mode_of_study_id;
        $moduleId = $importer->moduleId();

        $studentEnrolmentIds = $this->collectStudentEnrolmentIdsFromRows($rawRows, $headerRowIndex, dataRowOffset: 1);

        /** @var Collection<string, Collection<int, CourseWorkMark>> $existingMarksByKey */
        $existingMarksByKey = $studentEnrolmentIds === []
            ? collect()
            : CourseWorkMark::query()
                ->withTrashed()
                ->where('course_syllabus_module_id', $moduleId)
                ->whereIn('student_enrolment_id', $studentEnrolmentIds)
                ->whereNull('assessment_type_id')
                ->get()
                ->groupBy(fn (CourseWorkMark $mark): string => CourseWorkMarkImporter::markKey(
                    (int) $mark->student_enrolment_id,
                    (int) $mark->course_syllabus_module_id,
                    null,
                ));

        /** @var array<int, int|null> $modeOfStudyByEnrolmentId */
        $modeOfStudyByEnrolmentId = $studentEnrolmentIds === []
            ? []
            : StudentEnrolment::query()
                ->whereIn('id', $studentEnrolmentIds)
                ->pluck('mode_of_study_id', 'id')
                ->map(fn ($modeId): ?int => $modeId !== null ? (int) $modeId : null)
                ->all();

        $markOperations = [];
        $studentRows = [];
        $summary = [
            'total' => 0,
            'succeeded' => 0,
            'failed' => 0,
            'skipped' => 0,
            'creates' => 0,
            'updates' => 0,
        ];

        $studentRowNumber = 0;

        for ($index = $headerRowIndex + 1; $index < count($rawRows); $index++) {
            $studentRow = $rawRows[$index];

            if (! $this->rowHasContent($studentRow)) {
                continue;
            }

            $studentRowNumber++;
            $display = CourseWorkMarkImporter::displayFromWideRow($studentRow);
            $rowValues = array_values($studentRow);
            $studentEnrolmentId = isset($rowValues[0]) && is_numeric($rowValues[0])
                ? (int) $rowValues[0]
                : 0;
            $markValue = $rowValues[4] ?? null;
            $remarkValue = isset($rowValues[5]) && $rowValues[5] !== '' ? (string) $rowValues[5] : null;
            $markEmpty = $markValue === null || $markValue === '';

            if ($markEmpty) {
                $markKey = CourseWorkMarkImporter::markKey($studentEnrolmentId, $moduleId, null);
                $existing = $existingMarksByKey->get($markKey)?->first();

                if ($existing !== null && ! $existing->trashed()) {
                    $summary['skipped']++;
                    $studentRows[] = [
                        'rowNumber' => $studentRowNumber,
                        ...$display,
                        'className' => $display['className'],
                        'cellResult' => [
                            'mark' => $existing->mark !== null ? (int) $existing->mark : null,
                            'remark' => $existing->remark,
                            'action' => 'skip_unchanged',
                            'errors' => null,
                        ],
                        'raw' => $studentRow,
                    ];

                    continue;
                }

                $summary['skipped']++;
                $studentRows[] = [
                    'rowNumber' => $studentRowNumber,
                    ...$display,
                    'className' => $display['className'],
                    'cellResult' => [
                        'mark' => null,
                        'remark' => null,
                        'action' => 'skip_empty',
                        'errors' => null,
                    ],
                    'raw' => $studentRow,
                ];

                continue;
            }

            $studentModeOfStudyId = $modeOfStudyByEnrolmentId[$studentEnrolmentId] ?? null;

            if ($studentModeOfStudyId === null || $studentModeOfStudyId !== $expectedModeOfStudyId) {
                $summary['failed']++;
                $modeMismatchErrors = [
                    'import' => [__('academic_calendar.course_work_import_mode_of_study_mismatch')],
                ];
                $studentRows[] = [
                    'rowNumber' => $studentRowNumber,
                    ...$display,
                    'className' => $display['className'],
                    'cellResult' => [
                        'mark' => is_numeric($markValue) ? (int) $markValue : null,
                        'remark' => $remarkValue,
                        'action' => 'fail',
                        'errors' => $modeMismatchErrors,
                    ],
                    'raw' => $studentRow,
                ];
                $markOperations[] = [
                    'rowNumber' => $studentRowNumber,
                    'status' => 'failed',
                    'action' => 'fail',
                    'raw' => $studentRow,
                    'errors' => $modeMismatchErrors,
                    ...$display,
                ];

                continue;
            }

            $summary['total']++;

            try {
                $payload = $importer->extractMarkOnlyPayload($studentRow, $markValue, $remarkValue);

                if ($dryRun) {
                    $this->assertRowPermissionsForPreview($payload);
                } else {
                    $this->assertRowPermissions($payload);
                }

                $markKey = CourseWorkMarkImporter::markKeyFromPayload($payload);
                $existing = $existingMarksByKey->get($markKey)?->first();
                $action = ($existing !== null && ! $existing->trashed()) ? 'update' : 'create';

                if ($action === 'create') {
                    $summary['creates']++;
                } else {
                    $summary['updates']++;
                }

                $summary['succeeded']++;

                $studentRows[] = [
                    'rowNumber' => $studentRowNumber,
                    ...$display,
                    'className' => $display['className'],
                    'cellResult' => [
                        'mark' => $payload['mark'],
                        'remark' => $payload['remark'],
                        'action' => $action,
                        'errors' => null,
                    ],
                    'raw' => $studentRow,
                ];

                $markOperations[] = [
                    'rowNumber' => $studentRowNumber,
                    'status' => 'ready',
                    'action' => $action,
                    'payload' => $payload,
                    'raw' => $studentRow,
                    'errors' => null,
                    ...$display,
                    'mark' => $payload['mark'],
                ];
            } catch (Throwable $exception) {
                $summary['failed']++;
                $errors = $exception instanceof ValidationException
                    ? $exception->errors()
                    : ['import' => [$exception->getMessage()]];

                $studentRows[] = [
                    'rowNumber' => $studentRowNumber,
                    ...$display,
                    'className' => $display['className'],
                    'cellResult' => [
                        'mark' => is_numeric($markValue) ? (int) $markValue : null,
                        'remark' => $remarkValue,
                        'action' => 'fail',
                        'errors' => $errors,
                    ],
                    'raw' => $studentRow,
                ];

                $markOperations[] = [
                    'rowNumber' => $studentRowNumber,
                    'status' => 'failed',
                    'action' => 'fail',
                    'raw' => $studentRow,
                    'errors' => $errors,
                    ...$display,
                ];
            }
        }

        if ($studentRowNumber === 0) {
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
            'layout' => 'mark_only',
            'summary' => $summary,
            'assessmentColumns' => [
                ['id' => 0, 'name' => __('academic_calendar.course_work_mark'), 'weightPercent' => null],
            ],
            'markOperations' => $markOperations,
            'studentRows' => $studentRows,
        ];
    }

    /**
     * @param  array{
     *     studentRows: list<array<string, mixed>>,
     * }  $analysis
     * @return list<array<string, mixed>>
     */
    private function formatMarkOnlyPreviewRows(array $analysis): array
    {
        $rows = [];

        foreach ($analysis['studentRows'] as $studentRow) {
            $cell = $studentRow['cellResult'] ?? [
                'mark' => null,
                'remark' => null,
                'action' => 'skip_empty',
                'errors' => null,
            ];

            $rows[] = [
                'rowNumber' => $studentRow['rowNumber'],
                'studentName' => $studentRow['studentName'] ?? null,
                'studentNumber' => $studentRow['studentNumber'] ?? null,
                'className' => $studentRow['className'] ?? null,
                'marks' => [
                    0 => [
                        'mark' => $cell['mark'],
                        'action' => $cell['action'],
                        'errors' => $cell['errors'],
                    ],
                ],
                'remark' => $cell['remark'] ?? null,
            ];
        }

        return $rows;
    }

    /**
     * @param  array{
     *     studentRows: list<array<string, mixed>>,
     *     assessmentColumns: list<array{id: int, name: string, weightPercent: int|null}>,
     * }  $analysis
     * @return list<array<string, mixed>>
     */
    private function formatWidePreviewRows(array $analysis): array
    {
        $rows = [];

        foreach ($analysis['studentRows'] as $studentRow) {
            /** @var array<int, array{mark: int|null, action: string, errors: array<string, list<string>>|null}> $cellResults */
            $cellResults = $studentRow['cellResults'] ?? [];
            $marks = [];

            foreach ($analysis['assessmentColumns'] as $column) {
                $typeId = (int) $column['id'];
                $cell = $cellResults[$typeId] ?? [
                    'mark' => null,
                    'action' => 'skip_empty',
                    'errors' => null,
                ];

                $marks[$typeId] = [
                    'mark' => $cell['mark'],
                    'action' => $cell['action'],
                    'errors' => $cell['errors'],
                ];
            }

            $rows[] = [
                'rowNumber' => $studentRow['rowNumber'],
                'studentName' => $studentRow['studentName'] ?? null,
                'studentNumber' => $studentRow['studentNumber'] ?? null,
                'className' => $studentRow['className'] ?? null,
                'marks' => $marks,
            ];
        }

        return $rows;
    }

    /**
     * @return list<array<int|string, mixed>>
     */
    private function readRawRows(string $fullPath): array
    {
        $rows = [];
        $reader = SimpleExcelReader::create($fullPath)->noHeaderRow();

        foreach ($reader->getRows() as $row) {
            $rows[] = $row;
        }

        return $rows;
    }

    /**
     * @param  list<array<int|string, mixed>>  $rawRows
     */
    private function detectHeaderRowIndex(array $rawRows): ?int
    {
        foreach ($rawRows as $index => $row) {
            $values = array_map(
                static fn ($value): string => strtoupper(trim((string) $value)),
                array_values($row),
            );

            if (in_array('STUDENT_ENROLMENT_ID', $values, true)) {
                return $index;
            }

            if ($index > 50) {
                break;
            }
        }

        return null;
    }

    /**
     * @param  list<array<int|string, mixed>>  $rawRows
     * @return list<int>
     */
    private function collectStudentEnrolmentIdsFromRows(array $rawRows, int $headerRowIndex, int $dataRowOffset = 2): array
    {
        $ids = [];

        for ($index = $headerRowIndex + $dataRowOffset; $index < count($rawRows); $index++) {
            $studentRow = $rawRows[$index];

            if (! $this->rowHasContent($studentRow)) {
                continue;
            }

            $values = array_values($studentRow);
            $studentEnrolmentId = $values[0] ?? null;

            if ($studentEnrolmentId !== null && $studentEnrolmentId !== '' && is_numeric($studentEnrolmentId)) {
                $ids[] = (int) $studentEnrolmentId;
            }
        }

        return array_values(array_unique($ids));
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
            ->where('course_syllabus_module_id', $payload['courseSyllabusModuleId']);

        if (($payload['assessmentTypeId'] ?? null) === null) {
            $existing->whereNull('assessment_type_id');
        } else {
            $existing->where('assessment_type_id', $payload['assessmentTypeId']);
        }

        $existingMark = $existing->first();

        if ($existingMark !== null && ! $existingMark->trashed() && ! $user->can('update', CourseWorkMark::class)) {
            throw ValidationException::withMessages([
                'permission' => [__('academic_calendar.course_work_import_update_denied')],
            ]);
        }

        if (($existingMark === null || $existingMark->trashed()) && ! $user->can('create', CourseWorkMark::class)) {
            throw ValidationException::withMessages([
                'permission' => [__('academic_calendar.course_work_import_create_denied')],
            ]);
        }

        $module = CourseSyllabusModule::query()->findOrFail($payload['courseSyllabusModuleId']);
        $this->markService->assertMutationUnlocked(
            (int) $payload['studentEnrolmentId'],
            $module,
            ($payload['assessmentTypeId'] ?? null) !== null ? (int) $payload['assessmentTypeId'] : null,
        );
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
