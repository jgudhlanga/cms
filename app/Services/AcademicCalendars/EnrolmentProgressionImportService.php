<?php

declare(strict_types=1);

namespace App\Services\AcademicCalendars;

use App\Actions\Students\CompleteLevelEnrolmentAction;
use App\Actions\Students\ContinueAndReseatStudentsAction;
use App\Enums\AcademicCalendars\EnrolmentProgressionImportAction;
use App\Exceptions\Students\StudentEnrolmentProgressionException;
use App\Importers\AcademicCalendars\StudentNumberProgressionImporter;
use App\Models\AcademicCalendars\AcademicCalendarClass;
use App\Models\AcademicCalendars\AcademicCalendarStudentEnrolment;
use App\Models\AcademicCalendars\ClassConfig;
use App\Models\Institution\InstitutionDepartment;
use App\Models\Students\StudentEnrolment;
use App\Services\Students\StudentEnrolmentProgressionService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;
use RuntimeException;
use Throwable;

class EnrolmentProgressionImportService
{
    public function __construct(
        protected StudentNumberProgressionImporter $importer,
        protected StudentEnrolmentProgressionService $progression,
        protected CompleteLevelEnrolmentAction $completeLevelEnrolment,
        protected ContinueAndReseatStudentsAction $continueAndReseatStudents,
    ) {}

    /**
     * @return array{
     *     rows: list<array<string, mixed>>,
     *     summary: array{total: int, eligible: int, skipped: int},
     * }
     */
    public function preview(
        UploadedFile $file,
        InstitutionDepartment $department,
        ClassConfig $classConfig,
        EnrolmentProgressionImportAction $action,
        ?AcademicCalendarClass $restrictToClass = null,
    ): array {
        $parsed = $this->parseUploadedFile($file);
        $enrolmentsByNumber = $this->enrolmentsByStudentNumber($department, $classConfig, $restrictToClass);

        $rows = [];
        $eligible = 0;
        $skipped = 0;

        foreach ($parsed['rows'] as $parsedRow) {
            $studentNumber = (string) ($parsedRow['studentNumber'] ?? '');
            $enrolment = $enrolmentsByNumber->get(strtoupper($studentNumber));

            $evaluated = $this->evaluateRow($enrolment, $action, $restrictToClass);

            if ($evaluated['eligible']) {
                $eligible++;
            } else {
                $skipped++;
            }

            $rows[] = [
                'rowNumber' => $parsedRow['rowNumber'],
                'studentNumber' => $studentNumber,
                'studentEnrolmentId' => $evaluated['studentEnrolmentId'],
                'studentName' => $evaluated['studentName'],
                'phaseLabel' => $evaluated['phaseLabel'],
                'status' => $evaluated['status'],
                'eligible' => $evaluated['eligible'],
                'skipReason' => $evaluated['skipReason'],
                'academicCalendarClassId' => $evaluated['academicCalendarClassId'],
            ];
        }

        return [
            'rows' => $rows,
            'summary' => [
                'total' => count($rows),
                'eligible' => $eligible,
                'skipped' => $skipped,
            ],
        ];
    }

    /**
     * @param  list<array{studentEnrolmentId: int, academicCalendarClassId?: int|null}>  $selectedRows
     * @return array{processed: int, skipped: int}
     */
    public function process(
        array $selectedRows,
        InstitutionDepartment $department,
        ClassConfig $classConfig,
        EnrolmentProgressionImportAction $action,
        ?AcademicCalendarClass $restrictToClass = null,
        ?int $triggeredBy = null,
    ): array {
        $enrolmentIds = array_values(array_unique(array_filter(array_map(
            static fn (array $row): int => (int) ($row['studentEnrolmentId'] ?? 0),
            $selectedRows,
        ))));

        if ($enrolmentIds === []) {
            return ['processed' => 0, 'skipped' => 0];
        }

        $enrolments = $this->scopedEnrolmentsQuery($department, $classConfig, $restrictToClass)
            ->whereIn('student_enrolments.id', $enrolmentIds)
            ->with([
                'studentApplication.departmentLevel.level',
                'studentEnrolmentStatus',
                'departmentLevel.level',
                'studentSemesters.semester',
                'studentSemesters.studentEnrolmentStatus',
                'student.user',
            ])
            ->get()
            ->keyBy('id');

        $processed = 0;
        $skipped = 0;

        if ($action === EnrolmentProgressionImportAction::AdvancePhase) {
            $byClass = [];
            foreach ($selectedRows as $row) {
                $enrolmentId = (int) ($row['studentEnrolmentId'] ?? 0);
                $enrolment = $enrolments->get($enrolmentId);
                if (! $enrolment instanceof StudentEnrolment) {
                    $skipped++;

                    continue;
                }

                $evaluated = $this->evaluateRow($enrolment, $action, $restrictToClass);
                if (! $evaluated['eligible']) {
                    $skipped++;

                    continue;
                }

                $classId = (int) ($evaluated['academicCalendarClassId'] ?? $row['academicCalendarClassId'] ?? 0);
                if ($classId < 1) {
                    $skipped++;

                    continue;
                }

                $byClass[$classId][] = $enrolment;
            }

            foreach ($byClass as $classId => $classEnrolments) {
                $sourceClass = AcademicCalendarClass::query()->find($classId);
                if (! $sourceClass instanceof AcademicCalendarClass) {
                    $skipped += count($classEnrolments);

                    continue;
                }

                $result = $this->continueAndReseatStudents->execute(
                    collect($classEnrolments),
                    $sourceClass,
                    $triggeredBy,
                );
                $processed += (int) ($result['advanced'] ?? 0);
                $skipped += max(0, count($classEnrolments) - (int) ($result['advanced'] ?? 0));
            }

            return ['processed' => $processed, 'skipped' => $skipped];
        }

        foreach ($selectedRows as $row) {
            $enrolmentId = (int) ($row['studentEnrolmentId'] ?? 0);
            $enrolment = $enrolments->get($enrolmentId);

            if (! $enrolment instanceof StudentEnrolment) {
                $skipped++;

                continue;
            }

            $evaluated = $this->evaluateRow($enrolment, $action, $restrictToClass);
            if (! $evaluated['eligible']) {
                $skipped++;

                continue;
            }

            try {
                $this->completeLevelEnrolment->execute($enrolment);
                $processed++;
            } catch (StudentEnrolmentProgressionException) {
                $skipped++;
            }
        }

        return ['processed' => $processed, 'skipped' => $skipped];
    }

    /**
     * @return array{
     *     rows: list<array{rowNumber: int, studentNumber: string|null}>,
     *     headerRowNumber: int,
     * }
     */
    private function parseUploadedFile(UploadedFile $file): array
    {
        $absolute = $file->getRealPath();

        if (! is_string($absolute) || $absolute === '') {
            throw new RuntimeException(__('academic_calendar.progression_import_preview_failed'));
        }

        try {
            return $this->importer->parse($absolute);
        } catch (Throwable $exception) {
            throw new RuntimeException(
                $exception->getMessage() !== ''
                    ? $exception->getMessage()
                    : __('academic_calendar.progression_import_preview_failed'),
                previous: $exception,
            );
        }
    }

    /**
     * @return Collection<string, StudentEnrolment>
     */
    private function enrolmentsByStudentNumber(
        InstitutionDepartment $department,
        ClassConfig $classConfig,
        ?AcademicCalendarClass $restrictToClass,
    ): Collection {
        return $this->scopedEnrolmentsQuery($department, $classConfig, $restrictToClass)
            ->with([
                'studentApplication.departmentLevel.level',
                'studentEnrolmentStatus',
                'departmentLevel.level',
                'studentSemesters.semester',
                'studentSemesters.studentEnrolmentStatus',
                'student.user',
            ])
            ->get()
            ->keyBy(function (StudentEnrolment $enrolment): string {
                $number = (string) ($enrolment->student?->student_number ?? '');

                return strtoupper($number);
            })
            ->filter(fn (StudentEnrolment $enrolment, string $key): bool => $key !== '');
    }

    private function scopedEnrolmentsQuery(
        InstitutionDepartment $department,
        ClassConfig $classConfig,
        ?AcademicCalendarClass $restrictToClass,
    ) {
        return StudentEnrolment::query()
            ->select('student_enrolments.*')
            ->where('student_enrolments.institution_department_id', $department->id)
            ->where('student_enrolments.department_level_id', $classConfig->department_level_id)
            ->where('student_enrolments.department_course_id', $classConfig->department_course_id)
            ->where('student_enrolments.mode_of_study_id', $classConfig->mode_of_study_id)
            ->whereNull('student_enrolments.deleted_at')
            ->whereHas('academicCalendarStudentEnrolment', function ($query) use ($classConfig, $restrictToClass): void {
                $query
                    ->whereNull('deleted_at')
                    ->where('is_live', true)
                    ->whereHas('academicCalendarClass', function ($classQuery) use ($classConfig, $restrictToClass): void {
                        $classQuery
                            ->where('class_config_id', $classConfig->id)
                            ->when(
                                $restrictToClass instanceof AcademicCalendarClass,
                                fn ($q) => $q->where('id', $restrictToClass->id),
                            );
                    });
            });
    }

    /**
     * @return array{
     *     eligible: bool,
     *     skipReason: string|null,
     *     studentEnrolmentId: int|null,
     *     studentName: string|null,
     *     phaseLabel: string|null,
     *     status: string|null,
     *     academicCalendarClassId: int|null,
     * }
     */
    private function evaluateRow(
        ?StudentEnrolment $enrolment,
        EnrolmentProgressionImportAction $action,
        ?AcademicCalendarClass $restrictToClass,
    ): array {
        if (! $enrolment instanceof StudentEnrolment) {
            return [
                'eligible' => false,
                'skipReason' => __('academic_calendar.progression_import_not_found'),
                'studentEnrolmentId' => null,
                'studentName' => null,
                'phaseLabel' => null,
                'status' => null,
                'academicCalendarClassId' => null,
            ];
        }

        $enrolment->loadMissing(['student.user', 'studentEnrolmentStatus']);
        $currentSemester = $this->progression->currentStudentSemester($enrolment);
        $currentSemester?->loadMissing('semester');
        $phaseLabel = $currentSemester?->semester?->name
            ?? $currentSemester?->programmeSemester?->name;
        $status = $enrolment->studentEnrolmentStatus?->name
            ?? $this->progression->statusSlug($enrolment);
        $classId = $this->liveClassIdForEnrolment($enrolment, $restrictToClass);

        $eligible = match ($action) {
            EnrolmentProgressionImportAction::CompleteLevel => $this->progression->canCompleteLevel($enrolment),
            EnrolmentProgressionImportAction::AdvancePhase => $this->progression->canAdvanceToNextPhase($enrolment),
        };

        $skipReason = null;
        if (! $eligible) {
            $skipReason = match ($action) {
                EnrolmentProgressionImportAction::CompleteLevel => __('academic_calendar.progression_import_cannot_complete'),
                EnrolmentProgressionImportAction::AdvancePhase => $this->progression->cannotAdvanceToNextPhaseReason($enrolment)
                    ?? __('academic_calendar.progression_import_cannot_advance'),
            };
        }

        if ($eligible && $action === EnrolmentProgressionImportAction::AdvancePhase && ($classId === null || $classId < 1)) {
            $eligible = false;
            $skipReason = __('academic_calendar.progression_import_no_class');
        }

        $user = $enrolment->student?->user;

        return [
            'eligible' => $eligible,
            'skipReason' => $skipReason,
            'studentEnrolmentId' => (int) $enrolment->id,
            'studentName' => trim(sprintf('%s %s', (string) ($user?->first_name ?? ''), (string) ($user?->last_name ?? ''))),
            'phaseLabel' => is_string($phaseLabel) ? $phaseLabel : null,
            'status' => is_string($status) ? $status : null,
            'academicCalendarClassId' => $classId,
        ];
    }

    private function liveClassIdForEnrolment(
        StudentEnrolment $enrolment,
        ?AcademicCalendarClass $restrictToClass,
    ): ?int {
        $query = AcademicCalendarStudentEnrolment::query()
            ->where('student_enrolment_id', $enrolment->id)
            ->where('is_live', true)
            ->whereNull('deleted_at')
            ->when(
                $restrictToClass instanceof AcademicCalendarClass,
                fn ($q) => $q->where('academic_calendar_class_id', $restrictToClass->id),
            );

        $classId = $query->value('academic_calendar_class_id');

        return $classId !== null ? (int) $classId : null;
    }
}
