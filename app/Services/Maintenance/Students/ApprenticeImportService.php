<?php

declare(strict_types=1);

namespace App\Services\Maintenance\Students;

use App\Actions\Students\ContinueStudentEnrolmentAction;
use App\Enums\Shared\ClassListTypeEnum;
use App\Exceptions\Students\StudentEnrolmentResolutionException;
use App\Importers\Maintenance\ApprenticeImporter;
use App\Models\Students\Student;
use App\Models\Students\StudentApprentice;
use App\Models\Students\StudentApplication;
use App\Rules\ZimbabweanIdNumber;
use App\Services\Enrollment\EnrollmentLookupService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Throwable;

class ApprenticeImportService
{
    public function __construct(
        private readonly ApprenticeImporter $importer,
        private readonly EnrollmentLookupService $lookupService,
        private readonly FaultyStudentIdNumberAnalysis $idNumberAnalysis,
        private readonly ContinueStudentEnrolmentAction $continueStudentEnrolmentAction,
    ) {}

    /**
     * @return array{
     *     summary: array{
     *         total: int,
     *         found: int,
     *         notFound: int,
     *         invalid: int,
     *         alreadyApprentice: int,
     *         invalidId: int,
     *         selectable: int,
     *     },
     *     rows: list<array<string, mixed>>,
     * }
     */
    public function preview(UploadedFile $file, int $institutionDepartmentId, int $calendarYear): array
    {
        $storedPath = $file->store('apprentice-imports/previews', 'ingest');
        $absolutePath = Storage::disk('ingest')->path($storedPath);

        try {
            $parsed = $this->importer->parse($absolutePath);
        } finally {
            Storage::disk('ingest')->delete($storedPath);
        }

        $rows = [];
        $summary = [
            'total' => 0,
            'found' => 0,
            'notFound' => 0,
            'invalid' => 0,
            'alreadyApprentice' => 0,
            'invalidId' => 0,
            'selectable' => 0,
        ];

        foreach ($parsed['rows'] as $parsedRow) {
            $previewRow = $this->buildPreviewRowFromParsed(
                $parsedRow,
                $institutionDepartmentId,
                $calendarYear,
            );

            $rows[] = $previewRow;
            $summary['total']++;
            $summary[$this->summaryKeyForStatus($previewRow['status'])]++;

            if ($previewRow['isAlreadyApprentice']) {
                $summary['alreadyApprentice']++;
            }

            if ($previewRow['studentId'] !== null && ! $previewRow['idNumberValid']) {
                $summary['invalidId']++;
            }

            if ($previewRow['isSelectable']) {
                $summary['selectable']++;
            }
        }

        return [
            'summary' => $summary,
            'rows' => $rows,
        ];
    }

    /**
     * @param  list<array{
     *     rowNumber: int,
     *     studentApplicationId: int,
     *     apprenticeNumber?: string|null,
     *     employer?: string|null,
     * }>  $rows
     * @return array{
     *     summary: array{requested: int, moved: int, skipped: int},
     *     rows: list<array{rowNumber: int, status: string, reason?: string}>,
     * }
     */
    public function process(array $rows, int $institutionDepartmentId, int $calendarYear): array
    {
        $results = [];
        $moved = 0;
        $skipped = 0;

        foreach ($rows as $row) {
            $rowNumber = (int) $row['rowNumber'];
            $studentApplicationId = (int) $row['studentApplicationId'];
            $apprenticeNumber = isset($row['apprenticeNumber']) ? $this->nullableString($row['apprenticeNumber']) : null;
            $employer = isset($row['employer']) ? $this->nullableString($row['employer']) : null;

            $outcome = $this->processRow(
                $rowNumber,
                $studentApplicationId,
                $institutionDepartmentId,
                $calendarYear,
                $apprenticeNumber,
                $employer,
            );

            $results[] = $outcome;

            if ($outcome['status'] === 'moved') {
                $moved++;
            } else {
                $skipped++;
            }
        }

        return [
            'summary' => [
                'requested' => count($rows),
                'moved' => $moved,
                'skipped' => $skipped,
            ],
            'rows' => $results,
        ];
    }

    /**
     * @param  array{
     *     rowNumber: int,
     *     idNumber: string|null,
     *     studentNumber: string|null,
     *     apprenticeNumber: string|null,
     *     employer: string|null,
     * }  $parsedRow
     * @return array<string, mixed>
     */
    public function buildPreviewRowFromParsed(array $parsedRow, int $institutionDepartmentId, int $calendarYear): array
    {
        return $this->buildPreviewRow($parsedRow, $institutionDepartmentId, $calendarYear);
    }

    /**
     * @param  array{
     *     rowNumber: int,
     *     idNumber: string|null,
     *     studentNumber: string|null,
     *     apprenticeNumber: string|null,
     *     employer: string|null,
     * }  $parsedRow
     * @return array{row: array<string, mixed>}
     */
    public function refreshPreviewRow(array $parsedRow, int $institutionDepartmentId, int $calendarYear): array
    {
        return [
            'row' => $this->buildPreviewRowFromParsed($parsedRow, $institutionDepartmentId, $calendarYear),
        ];
    }

    /**
     * @param  array{
     *     rowNumber: int,
     *     idNumber: string|null,
     *     studentNumber: string|null,
     *     apprenticeNumber: string|null,
     *     employer: string|null,
     * }  $parsedRow
     * @return array<string, mixed>
     */
    private function buildPreviewRow(array $parsedRow, int $institutionDepartmentId, int $calendarYear): array
    {
        $idNumber = $parsedRow['idNumber'];
        $studentNumber = $parsedRow['studentNumber'];

        if ($idNumber === null && $studentNumber === null) {
            return $this->emptyEnrichmentRow($parsedRow, [
                'status' => 'invalid',
                'studentId' => null,
                'studentName' => null,
                'matchedBy' => null,
                'errors' => [__('trans.maintenance_apprentice_import_missing_identifier')],
                'skipReasons' => [__('trans.maintenance_apprentice_import_missing_identifier')],
            ]);
        }

        $resolved = $this->resolveStudent($idNumber, $studentNumber);
        $student = $resolved['student'];
        $matchedBy = $resolved['matchedBy'];

        if (! $student instanceof Student) {
            return $this->emptyEnrichmentRow($parsedRow, [
                'status' => 'not_found',
                'studentId' => null,
                'studentName' => null,
                'matchedBy' => null,
                'errors' => [__('trans.maintenance_apprentice_import_student_not_found')],
                'skipReasons' => [__('trans.maintenance_apprentice_import_student_not_found')],
            ]);
        }

        $application = $this->findApplication($student->id, $institutionDepartmentId, $calendarYear);

        if (! $application instanceof StudentApplication) {
            return $this->emptyEnrichmentRow($parsedRow, [
                'status' => 'not_found',
                'studentId' => $student->id,
                'studentName' => $student->user?->full_name,
                'matchedBy' => $matchedBy,
                'errors' => [__('trans.maintenance_apprentice_import_student_not_enrolled')],
                'skipReasons' => [__('trans.maintenance_apprentice_import_student_not_enrolled')],
                'idNumberValid' => ZimbabweanIdNumber::isValid((string) $student->id_number),
            ], $student);
        }

        return $this->enrichFoundRow($parsedRow, $student, $application, $calendarYear, $matchedBy);
    }

    /**
     * @param  array{
     *     rowNumber: int,
     *     idNumber: string|null,
     *     studentNumber: string|null,
     *     apprenticeNumber: string|null,
     *     employer: string|null,
     * }  $parsedRow
     * @return array<string, mixed>
     */
    private function enrichFoundRow(
        array $parsedRow,
        Student $student,
        StudentApplication $application,
        int $calendarYear,
        ?string $matchedBy,
    ): array {
        $classListStatus = $application->classList?->type;
        $classListStatusValue = $classListStatus instanceof ClassListTypeEnum
            ? $classListStatus->value
            : (is_string($classListStatus) ? $classListStatus : null);

        $idAnalysis = $this->analyzeStudentId($student);
        $idNumberValid = $idAnalysis['idNumberValid'];

        $isAlreadyApprentice = StudentApprentice::query()
            ->where('student_id', $student->id)
            ->where('calendar_year', $calendarYear)
            ->exists();

        $hasStudentNumber = is_string($student->student_number) && trim($student->student_number) !== '';

        $skipReasons = $this->buildSkipReasons(
            idNumberValid: $idNumberValid,
            isAlreadyApprentice: $isAlreadyApprentice,
            classListStatus: $classListStatusValue,
            hasStudentNumber: $hasStudentNumber,
        );

        $isSelectable = $skipReasons === [];

        return [
            ...$parsedRow,
            'status' => 'found',
            'studentId' => $student->id,
            'studentName' => $student->user?->full_name,
            'matchedBy' => $matchedBy,
            'storedIdNumber' => (string) $student->id_number,
            'errors' => [],
            'departmentCode' => $application->institutionDepartment?->department_code,
            'level' => $application->departmentLevel?->level?->name,
            'course' => $application->departmentCourse?->course?->name,
            'classListStatus' => $classListStatusValue,
            'studentApplicationId' => $application->id,
            'idNumberValid' => $idNumberValid,
            'suggestedIdNumber' => $idAnalysis['suggestedIdNumber'],
            'idRectificationStatus' => $idAnalysis['rectificationStatus'],
            'idConflict' => $idAnalysis['idConflict'],
            'isAlreadyApprentice' => $isAlreadyApprentice,
            'isSelectable' => $isSelectable,
            'skipReasons' => $skipReasons,
        ];
    }

    /**
     * @param  array{
     *     rowNumber: int,
     *     idNumber: string|null,
     *     studentNumber: string|null,
     *     apprenticeNumber: string|null,
     *     employer: string|null,
     * }  $parsedRow
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function emptyEnrichmentRow(array $parsedRow, array $overrides, ?Student $student = null): array
    {
        $idAnalysis = $student instanceof Student
            ? $this->analyzeStudentId($student)
            : [
                'idNumberValid' => array_key_exists('idNumberValid', $overrides) ? (bool) $overrides['idNumberValid'] : true,
                'suggestedIdNumber' => null,
                'rectificationStatus' => null,
                'idConflict' => null,
            ];

        if (array_key_exists('idNumberValid', $overrides)) {
            $idAnalysis['idNumberValid'] = (bool) $overrides['idNumberValid'];
        }

        return [
            ...$parsedRow,
            'status' => $overrides['status'],
            'studentId' => $overrides['studentId'] ?? null,
            'studentName' => $overrides['studentName'] ?? null,
            'matchedBy' => $overrides['matchedBy'] ?? null,
            'storedIdNumber' => $student instanceof Student ? (string) $student->id_number : null,
            'errors' => $overrides['errors'] ?? [],
            'departmentCode' => null,
            'level' => null,
            'course' => null,
            'classListStatus' => null,
            'studentApplicationId' => null,
            'idNumberValid' => $idAnalysis['idNumberValid'],
            'suggestedIdNumber' => $idAnalysis['suggestedIdNumber'],
            'idRectificationStatus' => $idAnalysis['rectificationStatus'],
            'idConflict' => $idAnalysis['idConflict'],
            'isAlreadyApprentice' => false,
            'isSelectable' => false,
            'skipReasons' => $overrides['skipReasons'] ?? [],
        ];
    }

    /**
     * @return array{
     *     idNumberValid: bool,
     *     suggestedIdNumber: string|null,
     *     rectificationStatus: string|null,
     *     idConflict: array<string, mixed>|null,
     * }
     */
    private function analyzeStudentId(Student $student): array
    {
        $idNumberValid = ZimbabweanIdNumber::isValid((string) $student->id_number);

        if ($idNumberValid) {
            return [
                'idNumberValid' => true,
                'suggestedIdNumber' => null,
                'rectificationStatus' => null,
                'idConflict' => null,
            ];
        }

        $analysis = $this->idNumberAnalysis->analyze($student);

        return [
            'idNumberValid' => false,
            'suggestedIdNumber' => $analysis['suggestedIdNumber'] ?? null,
            'rectificationStatus' => $analysis['rectificationStatus'] ?? null,
            'idConflict' => $analysis['conflict'],
        ];
    }

    /**
     * @return list<string>
     */
    private function buildSkipReasons(
        bool $idNumberValid,
        bool $isAlreadyApprentice,
        ?string $classListStatus,
        bool $hasStudentNumber,
    ): array {
        $reasons = [];

        if (! $idNumberValid) {
            $reasons[] = __('trans.maintenance_apprentice_import_skip_invalid_id');
        }

        if ($isAlreadyApprentice) {
            $reasons[] = __('trans.maintenance_apprentice_import_skip_already_apprentice');
        }

        if ($classListStatus === null) {
            $reasons[] = __('trans.maintenance_apprentice_import_skip_missing_class_list');
        } elseif ($classListStatus === ClassListTypeEnum::FAILED->value) {
            $reasons[] = __('trans.maintenance_apprentice_import_skip_failed_class_list');
        }

        if (! $hasStudentNumber) {
            $reasons[] = __('trans.maintenance_apprentice_import_skip_missing_student_number');
        }

        return $reasons;
    }

    /**
     * @return array{rowNumber: int, status: string, reason?: string}
     */
    private function processRow(
        int $rowNumber,
        int $studentApplicationId,
        int $institutionDepartmentId,
        int $calendarYear,
        ?string $apprenticeNumber,
        ?string $employer,
    ): array {
        $application = StudentApplication::query()
            ->with([
                'classList',
                'student.user',
                'institutionDepartment',
                'departmentLevel.level',
                'departmentCourse.course',
                'intakePeriod',
            ])
            ->whereKey($studentApplicationId)
            ->where('institution_department_id', $institutionDepartmentId)
            ->whereHas('intakePeriod', fn ($query) => $query->where('calendar_year', (string) $calendarYear))
            ->first();

        if (! $application instanceof StudentApplication) {
            return [
                'rowNumber' => $rowNumber,
                'status' => 'skipped',
                'reason' => __('trans.maintenance_apprentice_import_student_not_enrolled'),
            ];
        }

        $student = $application->student;

        if (! $student instanceof Student) {
            return [
                'rowNumber' => $rowNumber,
                'status' => 'skipped',
                'reason' => __('trans.maintenance_apprentice_import_student_not_found'),
            ];
        }

        $classListStatus = $application->classList?->type;
        $classListStatusValue = $classListStatus instanceof ClassListTypeEnum
            ? $classListStatus->value
            : (is_string($classListStatus) ? $classListStatus : null);

        $idNumberValid = ZimbabweanIdNumber::isValid((string) $student->id_number);
        $isAlreadyApprentice = StudentApprentice::query()
            ->where('student_id', $student->id)
            ->where('calendar_year', $calendarYear)
            ->exists();
        $hasStudentNumber = is_string($student->student_number) && trim($student->student_number) !== '';

        $skipReasons = $this->buildSkipReasons(
            idNumberValid: $idNumberValid,
            isAlreadyApprentice: $isAlreadyApprentice,
            classListStatus: $classListStatusValue,
            hasStudentNumber: $hasStudentNumber,
        );

        if ($skipReasons !== []) {
            return [
                'rowNumber' => $rowNumber,
                'status' => 'skipped',
                'reason' => $skipReasons[0],
            ];
        }

        try {
            DB::transaction(function () use ($application, $student, $calendarYear, $apprenticeNumber, $employer): void {
                $this->continueStudentEnrolmentAction->execute($application);

                StudentApprentice::query()->updateOrCreate(
                    [
                        'student_id' => $student->id,
                        'calendar_year' => $calendarYear,
                    ],
                    [
                        'employer' => $employer,
                        'apprentice_number' => $apprenticeNumber,
                    ],
                );
            });
        } catch (StudentEnrolmentResolutionException $exception) {
            return [
                'rowNumber' => $rowNumber,
                'status' => 'skipped',
                'reason' => $exception->getMessage(),
            ];
        } catch (Throwable $exception) {
            report($exception);

            return [
                'rowNumber' => $rowNumber,
                'status' => 'skipped',
                'reason' => __('trans.maintenance_apprentice_import_process_row_failed'),
            ];
        }

        return [
            'rowNumber' => $rowNumber,
            'status' => 'moved',
        ];
    }

    private function findApplication(int $studentId, int $institutionDepartmentId, int $calendarYear): ?StudentApplication
    {
        return StudentApplication::query()
            ->with([
                'classList',
                'institutionDepartment',
                'departmentLevel.level',
                'departmentCourse.course',
                'student.user',
            ])
            ->where('student_id', $studentId)
            ->where('institution_department_id', $institutionDepartmentId)
            ->whereHas('intakePeriod', fn ($query) => $query->where('calendar_year', (string) $calendarYear))
            ->latest('id')
            ->first();
    }

    /**
     * @return array{student: Student|null, matchedBy: string|null}
     */
    private function resolveStudent(?string $idNumber, ?string $studentNumber): array
    {
        if ($idNumber !== null) {
            $student = $this->lookupService->findStudentByNationalId($idNumber);

            if ($student instanceof Student) {
                return [
                    'student' => $student,
                    'matchedBy' => 'id_number',
                ];
            }
        }

        if ($studentNumber !== null) {
            $student = $this->lookupService->findStudentByStudentNumber($studentNumber);

            if ($student instanceof Student) {
                return [
                    'student' => $student,
                    'matchedBy' => 'student_number',
                ];
            }
        }

        return [
            'student' => null,
            'matchedBy' => null,
        ];
    }

    private function summaryKeyForStatus(string $status): string
    {
        return match ($status) {
            'found' => 'found',
            'invalid' => 'invalid',
            default => 'notFound',
        };
    }

    private function nullableString(mixed $value): ?string
    {
        if (! is_string($value)) {
            return null;
        }

        $trimmed = trim($value);

        return $trimmed === '' ? null : $trimmed;
    }
}
