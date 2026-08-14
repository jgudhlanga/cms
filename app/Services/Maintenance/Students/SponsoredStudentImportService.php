<?php

declare(strict_types=1);

namespace App\Services\Maintenance\Students;

use App\Actions\Students\ContinueStudentEnrolmentAction;
use App\Enums\Shared\ClassListTypeEnum;
use App\Enums\Shared\IdTypeEnum;
use App\Exceptions\Students\StudentEnrolmentResolutionException;
use App\Importers\Maintenance\SponsoredStudentImporter;
use App\Models\Students\Student;
use App\Models\Students\StudentApplication;
use App\Models\Students\StudentSponsor;
use App\Rules\ZimbabweanIdNumber;
use App\Services\Enrollment\EnrollmentLookupService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Throwable;

class SponsoredStudentImportService
{
    public function __construct(
        private readonly SponsoredStudentImporter $importer,
        private readonly EnrollmentLookupService $lookupService,
        private readonly ContinueStudentEnrolmentAction $continueStudentEnrolmentAction,
    ) {}

    /**
     * @return array{
     *     summary: array{
     *         total: int,
     *         found: int,
     *         notFound: int,
     *         invalid: int,
     *         alreadySponsored: int,
     *         invalidId: int,
     *         selectable: int,
     *     },
     *     rows: list<array<string, mixed>>,
     * }
     */
    public function preview(UploadedFile $file, int $calendarYear): array
    {
        $extension = strtolower((string) $file->getClientOriginalExtension());
        $filename = Str::uuid()->toString().($extension !== '' ? '.'.$extension : '.xlsx');
        $storedPath = $file->storeAs('sponsored-student-imports/previews', $filename, 'ingest');
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
            'alreadySponsored' => 0,
            'invalidId' => 0,
            'selectable' => 0,
        ];

        foreach ($parsed['rows'] as $parsedRow) {
            $previewRow = $this->buildPreviewRow($parsedRow, $calendarYear);

            $rows[] = $previewRow;
            $summary['total']++;
            $summary[$this->summaryKeyForStatus($previewRow['status'])]++;

            if ($previewRow['isAlreadySponsored']) {
                $summary['alreadySponsored']++;
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
     *     sponsor?: string|null,
     * }>  $rows
     * @return array{
     *     summary: array{requested: int, moved: int, skipped: int},
     *     rows: list<array{rowNumber: int, status: string, reason?: string}>,
     * }
     */
    public function process(array $rows, int $calendarYear): array
    {
        $results = [];
        $moved = 0;
        $skipped = 0;

        foreach ($rows as $row) {
            $rowNumber = (int) $row['rowNumber'];
            $studentApplicationId = (int) $row['studentApplicationId'];
            $sponsor = isset($row['sponsor']) ? $this->nullableString($row['sponsor']) : null;

            $outcome = $this->processRow(
                $rowNumber,
                $studentApplicationId,
                $calendarYear,
                $sponsor,
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
     *     studentNumber: string|null,
     *     sponsor: string|null,
     * }  $parsedRow
     * @return array<string, mixed>
     */
    private function buildPreviewRow(array $parsedRow, int $calendarYear): array
    {
        $studentNumber = $parsedRow['studentNumber'];

        if ($studentNumber === null) {
            return $this->emptyEnrichmentRow($parsedRow, [
                'status' => 'invalid',
                'studentId' => null,
                'studentName' => null,
                'matchedBy' => null,
                'errors' => [__('trans.maintenance_sponsored_students_import_missing_identifier')],
                'skipReasons' => [__('trans.maintenance_sponsored_students_import_missing_identifier')],
            ]);
        }

        $student = $this->lookupService->findStudentByStudentNumber($studentNumber);

        if (! $student instanceof Student) {
            return $this->emptyEnrichmentRow($parsedRow, [
                'status' => 'not_found',
                'studentId' => null,
                'studentName' => null,
                'matchedBy' => null,
                'errors' => [__('trans.maintenance_sponsored_students_import_student_not_found')],
                'skipReasons' => [__('trans.maintenance_sponsored_students_import_student_not_found')],
            ]);
        }

        $application = $this->findApplication($student->id, $calendarYear);

        if (! $application instanceof StudentApplication) {
            return $this->emptyEnrichmentRow($parsedRow, [
                'status' => 'not_found',
                'studentId' => $student->id,
                'studentName' => $student->user?->full_name,
                'matchedBy' => 'student_number',
                'errors' => [__('trans.maintenance_sponsored_students_import_student_not_enrolled')],
                'skipReasons' => [__('trans.maintenance_sponsored_students_import_student_not_enrolled')],
                'idNumberValid' => $this->idNumberIsValidForSkip($student),
            ], $student);
        }

        return $this->enrichFoundRow($parsedRow, $student, $application, $calendarYear);
    }

    /**
     * @param  array{
     *     rowNumber: int,
     *     studentNumber: string|null,
     *     sponsor: string|null,
     * }  $parsedRow
     * @return array<string, mixed>
     */
    private function enrichFoundRow(
        array $parsedRow,
        Student $student,
        StudentApplication $application,
        int $calendarYear,
    ): array {
        $classListStatus = $application->classList?->type;
        $classListStatusValue = $classListStatus instanceof ClassListTypeEnum
            ? $classListStatus->value
            : (is_string($classListStatus) ? $classListStatus : null);

        $idNumberValid = $this->idNumberIsValidForSkip($student);

        $existingSponsor = StudentSponsor::query()
            ->where('student_id', $student->id)
            ->where('calendar_year', $calendarYear)
            ->first();

        $isAlreadySponsored = $existingSponsor instanceof StudentSponsor;
        $hasStudentNumber = is_string($student->student_number) && trim($student->student_number) !== '';

        $skipReasons = $this->buildSkipReasons(
            idNumberValid: $idNumberValid,
            classListStatus: $classListStatusValue,
            hasStudentNumber: $hasStudentNumber,
        );

        return [
            ...$parsedRow,
            'status' => 'found',
            'studentId' => $student->id,
            'studentName' => $student->user?->full_name,
            'matchedBy' => 'student_number',
            'storedIdNumber' => $this->nullableString($student->id_number),
            'passportNumber' => $this->nullableString($student->passport_number),
            'identityNumber' => $this->identityNumber($student),
            'errors' => [],
            'departmentCode' => $application->institutionDepartment?->department_code,
            'level' => $application->departmentLevel?->level?->name,
            'course' => $application->departmentCourse?->course?->name,
            'classListStatus' => $classListStatusValue,
            'studentApplicationId' => $application->id,
            'idNumberValid' => $idNumberValid,
            'isAlreadySponsored' => $isAlreadySponsored,
            'existingSponsor' => $existingSponsor?->sponsor,
            'action' => $isAlreadySponsored ? 'update' : 'create',
            'isSelectable' => $skipReasons === [],
            'skipReasons' => $skipReasons,
        ];
    }

    /**
     * @param  array{
     *     rowNumber: int,
     *     studentNumber: string|null,
     *     sponsor: string|null,
     * }  $parsedRow
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function emptyEnrichmentRow(array $parsedRow, array $overrides, ?Student $student = null): array
    {
        $idNumberValid = $student instanceof Student
            ? $this->idNumberIsValidForSkip($student)
            : (array_key_exists('idNumberValid', $overrides) ? (bool) $overrides['idNumberValid'] : true);

        return [
            ...$parsedRow,
            'status' => $overrides['status'],
            'studentId' => $overrides['studentId'] ?? null,
            'studentName' => $overrides['studentName'] ?? null,
            'matchedBy' => $overrides['matchedBy'] ?? null,
            'storedIdNumber' => $student instanceof Student ? $this->nullableString($student->id_number) : null,
            'passportNumber' => $student instanceof Student ? $this->nullableString($student->passport_number) : null,
            'identityNumber' => $student instanceof Student ? $this->identityNumber($student) : null,
            'errors' => $overrides['errors'] ?? [],
            'departmentCode' => null,
            'level' => null,
            'course' => null,
            'classListStatus' => null,
            'studentApplicationId' => null,
            'idNumberValid' => $idNumberValid,
            'isAlreadySponsored' => false,
            'existingSponsor' => null,
            'action' => null,
            'isSelectable' => false,
            'skipReasons' => $overrides['skipReasons'] ?? [],
        ];
    }

    /**
     * @return list<string>
     */
    private function buildSkipReasons(
        bool $idNumberValid,
        ?string $classListStatus,
        bool $hasStudentNumber,
    ): array {
        $reasons = [];

        if (! $idNumberValid) {
            $reasons[] = __('trans.maintenance_sponsored_students_import_skip_invalid_id');
        }

        if ($classListStatus === null) {
            $reasons[] = __('trans.maintenance_sponsored_students_import_skip_missing_class_list');
        } elseif ($classListStatus === ClassListTypeEnum::FAILED->value) {
            $reasons[] = __('trans.maintenance_sponsored_students_import_skip_failed_class_list');
        }

        if (! $hasStudentNumber) {
            $reasons[] = __('trans.maintenance_sponsored_students_import_skip_missing_student_number');
        }

        return $reasons;
    }

    /**
     * @return array{rowNumber: int, status: string, reason?: string}
     */
    private function processRow(
        int $rowNumber,
        int $studentApplicationId,
        int $calendarYear,
        ?string $sponsor,
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
            ->whereHas('intakePeriod', fn ($query) => $query->where('calendar_year', (string) $calendarYear))
            ->first();

        if (! $application instanceof StudentApplication) {
            return [
                'rowNumber' => $rowNumber,
                'status' => 'skipped',
                'reason' => __('trans.maintenance_sponsored_students_import_student_not_enrolled'),
            ];
        }

        $student = $application->student;

        if (! $student instanceof Student) {
            return [
                'rowNumber' => $rowNumber,
                'status' => 'skipped',
                'reason' => __('trans.maintenance_sponsored_students_import_student_not_found'),
            ];
        }

        $classListStatus = $application->classList?->type;
        $classListStatusValue = $classListStatus instanceof ClassListTypeEnum
            ? $classListStatus->value
            : (is_string($classListStatus) ? $classListStatus : null);

        $idNumberValid = $this->idNumberIsValidForSkip($student);
        $hasStudentNumber = is_string($student->student_number) && trim($student->student_number) !== '';

        $skipReasons = $this->buildSkipReasons(
            idNumberValid: $idNumberValid,
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
            DB::transaction(function () use ($application, $student, $calendarYear, $sponsor): void {
                $this->continueStudentEnrolmentAction->execute($application);

                StudentSponsor::query()->updateOrCreate(
                    [
                        'student_id' => $student->id,
                        'calendar_year' => $calendarYear,
                    ],
                    [
                        'sponsor' => $sponsor,
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
                'reason' => __('trans.maintenance_sponsored_students_import_process_row_failed'),
            ];
        }

        return [
            'rowNumber' => $rowNumber,
            'status' => 'moved',
        ];
    }

    private function findApplication(int $studentId, int $calendarYear): ?StudentApplication
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
            ->whereHas('intakePeriod', fn ($query) => $query->where('calendar_year', (string) $calendarYear))
            ->latest('id')
            ->first();
    }

    private function idNumberIsValidForSkip(Student $student): bool
    {
        if (! $this->shouldValidateZimbabweanId($student)) {
            return true;
        }

        return ZimbabweanIdNumber::isValid((string) $student->id_number);
    }

    private function shouldValidateZimbabweanId(Student $student): bool
    {
        $idNumber = trim((string) $student->id_number);

        if ($idNumber === '') {
            return false;
        }

        $student->loadMissing('idType');

        return $student->idType?->name !== IdTypeEnum::FOREIGN_PASSPORT_NUMBER->value;
    }

    private function identityNumber(Student $student): ?string
    {
        $idNumber = $this->nullableString($student->id_number);

        if ($idNumber !== null) {
            return $idNumber;
        }

        return $this->nullableString($student->passport_number);
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
