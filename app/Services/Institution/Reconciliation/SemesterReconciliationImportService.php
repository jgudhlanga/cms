<?php

declare(strict_types=1);

namespace App\Services\Institution\Reconciliation;

use App\Actions\Students\ConfirmStudyPositionAction;
use App\Actions\Students\SetStudentEnrolmentCurrentPhaseAction;
use App\Enums\Students\StudyPositionAnswerEnum;
use App\Enums\Students\StudyPositionSourceEnum;
use App\Importers\Institution\SemesterReconciliationImporter;
use App\Models\Institution\DepartmentCourse;
use App\Models\Institution\DepartmentLevel;
use App\Models\Institution\DepartmentLevelCourse;
use App\Models\Institution\InstitutionDepartment;
use App\Models\Institution\ProgrammeSemester;
use App\Models\Students\Student;
use App\Models\Students\StudentEnrolment;
use App\Models\Students\StudentSemester;
use App\Models\Users\User;
use App\Services\Enrollment\EnrollmentLookupService;
use App\Services\Institution\ProgrammeSemesterResolver;
use App\Services\Students\StudyPosition\CurrentStudyPeriod;
use App\Services\Students\StudyPosition\CurrentStudyPeriodResolver;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use InvalidArgumentException;
use Throwable;

class SemesterReconciliationImportService
{
    public function __construct(
        private readonly SemesterReconciliationImporter $importer,
        private readonly EnrollmentLookupService $lookupService,
        private readonly DepartmentReconciliationOfferingMatcher $offeringMatcher,
        private readonly ProgrammeSemesterResolver $programmeSemesterResolver,
        private readonly SetStudentEnrolmentCurrentPhaseAction $setStudentEnrolmentCurrentPhaseAction,
        private readonly CurrentStudyPeriodResolver $studyPeriods,
        private readonly ConfirmStudyPositionAction $confirmStudyPosition,
    ) {}

    /**
     * @return array{
     *     summary: array<string, int>,
     *     rows: list<array<string, mixed>>,
     *     extras: list<array<string, mixed>>,
     * }
     */
    public function preview(
        InstitutionDepartment $department,
        UploadedFile $file,
        int $calendarYear,
        ?int $modeOfStudyId = null,
        ?int $departmentLevelId = null,
        ?int $departmentCourseId = null,
    ): array {
        $extension = strtolower((string) $file->getClientOriginalExtension());
        $filename = Str::uuid()->toString().($extension !== '' ? '.'.$extension : '.xlsx');
        $storedPath = $file->storeAs('department-reconciliation/semester-reconciliation', $filename, 'ingest');
        $absolutePath = Storage::disk('ingest')->path($storedPath);

        try {
            $parsed = $this->importer->parse($absolutePath);
        } finally {
            Storage::disk('ingest')->delete($storedPath);
        }

        $rows = [];
        $summary = [
            'total' => 0,
            'matched' => 0,
            'mismatch' => 0,
            'notEnrolled' => 0,
            'wrongPlace' => 0,
            'invalid' => 0,
            'selectable' => 0,
            'extras' => 0,
        ];

        $fileStudentNumbers = [];

        foreach ($parsed['rows'] as $parsedRow) {
            $previewRow = $this->buildPreviewRow(
                $department,
                $parsedRow,
                $calendarYear,
                $modeOfStudyId,
                $departmentLevelId,
                $departmentCourseId,
            );

            $rows[] = $previewRow;
            $summary['total']++;
            $summary[$this->summaryKeyForStatus($previewRow['status'])]++;

            if ($previewRow['isSelectable']) {
                $summary['selectable']++;
            }

            if (is_string($previewRow['studentNumber']) && $previewRow['studentNumber'] !== '') {
                $fileStudentNumbers[] = EnrollmentLookupService::normalizeStudentNumber($previewRow['studentNumber']);
            }
        }

        $extras = $this->buildExtras(
            $department,
            $calendarYear,
            array_values(array_unique($fileStudentNumbers)),
            $modeOfStudyId,
            $departmentLevelId,
            $departmentCourseId,
        );
        $summary['extras'] = count($extras);

        return [
            'summary' => $summary,
            'rows' => $rows,
            'extras' => $extras,
        ];
    }

    /**
     * Moves the selected mismatches and, for the current year, records the department's word as the
     * students' study position. Rows the preview found already aligned can be confirmed as they are.
     *
     * @param  list<array{rowNumber: int, studentEnrolmentId: int, programmeSemesterId: int}>  $rows
     * @param  list<array{studentEnrolmentId: int, programmeSemesterId: int}>  $matchedRows
     * @return array{
     *     summary: array{requested: int, moved: int, skipped: int, confirmed: int},
     *     rows: list<array{rowNumber: int, status: string, reason?: string}>,
     * }
     */
    public function process(
        InstitutionDepartment $department,
        array $rows,
        array $matchedRows = [],
        ?User $actor = null,
    ): array {
        $results = [];
        $moved = 0;
        $skipped = 0;
        $confirmed = 0;

        foreach ($rows as $row) {
            $outcome = $this->processRow(
                $department,
                (int) $row['rowNumber'],
                (int) $row['studentEnrolmentId'],
                (int) $row['programmeSemesterId'],
                $actor,
            );

            $confirmed += (int) ($outcome['confirmed'] ?? false);
            unset($outcome['confirmed']);
            $results[] = $outcome;

            if ($outcome['status'] === 'moved') {
                $moved++;
            } else {
                $skipped++;
            }
        }

        foreach ($matchedRows as $matched) {
            $confirmed += (int) $this->confirmAlignedRow(
                $department,
                (int) $matched['studentEnrolmentId'],
                (int) $matched['programmeSemesterId'],
                $actor,
            );
        }

        return [
            'summary' => [
                'requested' => count($rows),
                'moved' => $moved,
                'skipped' => $skipped,
                'confirmed' => $confirmed,
            ],
            'rows' => $results,
        ];
    }

    /**
     * @param  array{
     *     rowNumber: int,
     *     studentNumber: string|null,
     *     level: string|null,
     *     course: string|null,
     *     programmePhase: string|null,
     * }  $parsedRow
     * @return array<string, mixed>
     */
    private function buildPreviewRow(
        InstitutionDepartment $department,
        array $parsedRow,
        int $calendarYear,
        ?int $modeOfStudyId,
        ?int $filterLevelId,
        ?int $filterCourseId,
    ): array {
        $base = [
            'rowNumber' => $parsedRow['rowNumber'],
            'studentNumber' => $parsedRow['studentNumber'],
            'fileLevel' => $parsedRow['level'],
            'fileCourse' => $parsedRow['course'],
            'filePhase' => $parsedRow['programmePhase'],
            'studentId' => null,
            'studentName' => null,
            'studentEnrolmentId' => null,
            'programmeSemesterId' => null,
            'systemLevel' => null,
            'systemCourse' => null,
            'systemPhase' => null,
            'highlight' => null,
            'errors' => [],
            'skipReasons' => [],
            'isSelectable' => false,
            'status' => 'invalid',
        ];

        if ($parsedRow['studentNumber'] === null) {
            return array_merge($base, [
                'errors' => [__('trans.department_reconciliation_missing_student_number')],
                'skipReasons' => [__('trans.department_reconciliation_missing_student_number')],
            ]);
        }

        if ($parsedRow['programmePhase'] === null) {
            return array_merge($base, [
                'errors' => [__('trans.department_semester_reconciliation_missing_phase')],
                'skipReasons' => [__('trans.department_semester_reconciliation_missing_phase')],
            ]);
        }

        $departmentLevel = $this->offeringMatcher->findDepartmentLevel($department, $parsedRow['level']);
        $departmentCourse = $this->offeringMatcher->findDepartmentCourse($department, $parsedRow['course']);

        if ($parsedRow['level'] === null || ! $departmentLevel instanceof DepartmentLevel) {
            return array_merge($base, [
                'errors' => [__('trans.department_reconciliation_unknown_level')],
                'skipReasons' => [__('trans.department_reconciliation_unknown_level')],
            ]);
        }

        if ($parsedRow['course'] === null || ! $departmentCourse instanceof DepartmentCourse) {
            return array_merge($base, [
                'errors' => [__('trans.department_reconciliation_unknown_course')],
                'skipReasons' => [__('trans.department_reconciliation_unknown_course')],
            ]);
        }

        if ($filterLevelId !== null && (int) $departmentLevel->id !== $filterLevelId) {
            return array_merge($base, [
                'errors' => [__('trans.department_reconciliation_filtered_out')],
                'skipReasons' => [__('trans.department_reconciliation_filtered_out')],
            ]);
        }

        if ($filterCourseId !== null && (int) $departmentCourse->id !== $filterCourseId) {
            return array_merge($base, [
                'errors' => [__('trans.department_reconciliation_filtered_out')],
                'skipReasons' => [__('trans.department_reconciliation_filtered_out')],
            ]);
        }

        $offering = DepartmentLevelCourse::query()
            ->where('department_level_id', $departmentLevel->id)
            ->where('department_course_id', $departmentCourse->id)
            ->with(['programmeSemesters', 'departmentLevel.level'])
            ->first();

        if (! $offering instanceof DepartmentLevelCourse) {
            return array_merge($base, [
                'errors' => [__('trans.department_semester_reconciliation_missing_offering')],
                'skipReasons' => [__('trans.department_semester_reconciliation_missing_offering')],
            ]);
        }

        $targetPhase = $this->offeringMatcher->findProgrammeSemester($offering, $parsedRow['programmePhase']);

        if (! $targetPhase instanceof ProgrammeSemester) {
            return array_merge($base, [
                'errors' => [__('trans.department_semester_reconciliation_unknown_phase')],
                'skipReasons' => [__('trans.department_semester_reconciliation_unknown_phase')],
            ]);
        }

        $student = $this->lookupService->findStudentByStudentNumber($parsedRow['studentNumber']);

        if (! $student instanceof Student) {
            return array_merge($base, [
                'status' => 'not_enrolled',
                'errors' => [__('trans.department_reconciliation_student_not_found')],
                'skipReasons' => [__('trans.department_reconciliation_student_not_found')],
            ]);
        }

        $base['studentId'] = $student->id;
        $base['studentName'] = $student->user?->full_name;
        $base['programmeSemesterId'] = $targetPhase->id;
        $base['filePhase'] = $targetPhase->name;

        $enrolment = StudentEnrolment::query()
            ->where('student_id', $student->id)
            ->where('institution_department_id', $department->id)
            ->whereHas('academicCalendar', function (Builder $query) use ($calendarYear): void {
                $query->where('calendar_year', (string) $calendarYear);
            })
            ->when(
                $modeOfStudyId !== null,
                fn (Builder $query) => $query->where('mode_of_study_id', $modeOfStudyId),
            )
            ->with([
                'departmentLevel.level',
                'departmentCourse.course',
                'studentSemesters.semester',
                'studentSemesters.programmeSemester',
                'semester',
            ])
            ->orderByDesc('id')
            ->first();

        if (! $enrolment instanceof StudentEnrolment) {
            return array_merge($base, [
                'status' => 'not_enrolled',
                'errors' => [__('trans.department_semester_reconciliation_not_enrolled')],
                'skipReasons' => [__('trans.department_semester_reconciliation_not_enrolled')],
            ]);
        }

        $base['studentEnrolmentId'] = $enrolment->id;
        $base['systemLevel'] = $enrolment->departmentLevel?->level?->name;
        $base['systemCourse'] = $enrolment->departmentCourse?->course?->name;

        $levelMatches = (int) $enrolment->department_level_id === (int) $departmentLevel->id;
        $courseMatches = (int) $enrolment->department_course_id === (int) $departmentCourse->id;

        if (! $levelMatches || ! $courseMatches) {
            return array_merge($base, [
                'status' => 'wrong_place',
                'systemPhase' => $this->currentPhaseLabel($enrolment),
                'highlight' => __('trans.department_enrolment_vs_class_list_wrong_place_here'),
                'errors' => [__('trans.department_enrolment_vs_class_list_wrong_place_here')],
                'skipReasons' => [__('trans.department_enrolment_vs_class_list_wrong_place_here')],
            ]);
        }

        $systemPhase = $this->resolveCurrentProgrammeSemester($enrolment);
        $systemLabel = $systemPhase?->name ?? $this->currentPhaseLabel($enrolment);
        $base['systemPhase'] = $systemLabel;

        if ($systemPhase instanceof ProgrammeSemester
            && (int) $systemPhase->id === (int) $targetPhase->id) {
            return array_merge($base, [
                'status' => 'matched',
                'skipReasons' => [__('trans.department_semester_reconciliation_already_aligned')],
            ]);
        }

        return array_merge($base, [
            'status' => 'mismatch',
            'isSelectable' => true,
            'highlight' => __('trans.department_semester_reconciliation_phase_mismatch'),
        ]);
    }

    private function resolveCurrentProgrammeSemester(StudentEnrolment $enrolment): ?ProgrammeSemester
    {
        $current = $enrolment->currentStudentSemester();

        if ($current instanceof StudentSemester) {
            $fromRow = $this->programmeSemesterResolver->programmeSemesterForStudentSemester($current);

            if ($fromRow instanceof ProgrammeSemester) {
                return $fromRow;
            }
        }

        return null;
    }

    private function currentPhaseLabel(StudentEnrolment $enrolment): ?string
    {
        $phase = $this->resolveCurrentProgrammeSemester($enrolment);

        if ($phase instanceof ProgrammeSemester) {
            return $phase->name;
        }

        $current = $enrolment->currentStudentSemester();

        if ($current?->semester?->name) {
            return (string) $current->semester->name;
        }

        return $enrolment->semester?->name;
    }

    /**
     * @param  list<string>  $fileStudentNumbers
     * @return list<array<string, mixed>>
     */
    private function buildExtras(
        InstitutionDepartment $department,
        int $calendarYear,
        array $fileStudentNumbers,
        ?int $modeOfStudyId,
        ?int $departmentLevelId,
        ?int $departmentCourseId,
    ): array {
        $fileLookup = array_fill_keys($fileStudentNumbers, true);

        $enrolments = StudentEnrolment::query()
            ->where('institution_department_id', $department->id)
            ->whereHas('academicCalendar', function (Builder $query) use ($calendarYear): void {
                $query->where('calendar_year', (string) $calendarYear);
            })
            ->when(
                $modeOfStudyId !== null,
                fn (Builder $query) => $query->where('mode_of_study_id', $modeOfStudyId),
            )
            ->when(
                $departmentLevelId !== null,
                fn (Builder $query) => $query->where('department_level_id', $departmentLevelId),
            )
            ->when(
                $departmentCourseId !== null,
                fn (Builder $query) => $query->where('department_course_id', $departmentCourseId),
            )
            ->with([
                'student.user',
                'departmentLevel.level',
                'departmentCourse.course',
                'studentSemesters.semester',
                'studentSemesters.programmeSemester',
                'semester',
            ])
            ->get();

        $extras = [];

        foreach ($enrolments as $enrolment) {
            $studentNumber = $enrolment->student?->student_number;

            if (! is_string($studentNumber) || $studentNumber === '') {
                continue;
            }

            $normalized = EnrollmentLookupService::normalizeStudentNumber($studentNumber);

            if (isset($fileLookup[$normalized])) {
                continue;
            }

            $extras[] = [
                'studentEnrolmentId' => $enrolment->id,
                'studentId' => $enrolment->student_id,
                'studentNumber' => $studentNumber,
                'studentName' => $enrolment->student?->user?->full_name,
                'level' => $enrolment->departmentLevel?->level?->name,
                'course' => $enrolment->departmentCourse?->course?->name,
                'systemPhase' => $this->currentPhaseLabel($enrolment),
                'highlight' => __('trans.department_enrolment_vs_class_list_extra_on_system'),
            ];
        }

        return $extras;
    }

    /**
     * @return array{rowNumber: int, status: string, reason?: string, confirmed?: bool}
     */
    private function processRow(
        InstitutionDepartment $department,
        int $rowNumber,
        int $studentEnrolmentId,
        int $programmeSemesterId,
        ?User $actor,
    ): array {
        try {
            return DB::transaction(function () use (
                $department,
                $rowNumber,
                $studentEnrolmentId,
                $programmeSemesterId,
                $actor,
            ): array {
                $enrolment = StudentEnrolment::query()
                    ->with(['studentSemesters.semester', 'departmentLevel.level', 'departmentCourse'])
                    ->find($studentEnrolmentId);

                if (! $enrolment instanceof StudentEnrolment
                    || (int) $enrolment->institution_department_id !== (int) $department->id) {
                    return [
                        'rowNumber' => $rowNumber,
                        'status' => 'skipped',
                        'reason' => __('trans.department_semester_reconciliation_not_enrolled'),
                    ];
                }

                $programmeSemester = ProgrammeSemester::query()->find($programmeSemesterId);

                if (! $programmeSemester instanceof ProgrammeSemester) {
                    return [
                        'rowNumber' => $rowNumber,
                        'status' => 'skipped',
                        'reason' => __('trans.department_semester_reconciliation_unknown_phase'),
                    ];
                }

                $currentPeriod = $this->currentPeriodFor($enrolment);

                // A current-year file states where the student is now, so pin this period's slot;
                // back-year files keep the usual slot mapping.
                $this->setStudentEnrolmentCurrentPhaseAction->execute(
                    $enrolment,
                    $programmeSemester,
                    $currentPeriod?->slot,
                );

                return [
                    'rowNumber' => $rowNumber,
                    'status' => 'moved',
                    'confirmed' => $currentPeriod !== null
                        && $this->recordDepartmentConfirmation($enrolment, $programmeSemester, $actor, $rowNumber),
                ];
            });
        } catch (InvalidArgumentException $exception) {
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
                'reason' => __('trans.department_semester_reconciliation_process_row_failed'),
            ];
        }
    }

    private function confirmAlignedRow(
        InstitutionDepartment $department,
        int $studentEnrolmentId,
        int $programmeSemesterId,
        ?User $actor,
    ): bool {
        $enrolment = StudentEnrolment::query()
            ->with(['studentSemesters.semester', 'studentSemesters.programmeSemester', 'departmentLevel.level'])
            ->find($studentEnrolmentId);

        if (! $enrolment instanceof StudentEnrolment
            || (int) $enrolment->institution_department_id !== (int) $department->id
            || $this->currentPeriodFor($enrolment) === null) {
            return false;
        }

        // Re-checked here: the preview is not stored, so a forged "aligned" row must not confirm anything.
        $systemPhase = $this->resolveCurrentProgrammeSemester($enrolment);

        if (! $systemPhase instanceof ProgrammeSemester || (int) $systemPhase->id !== $programmeSemesterId) {
            return false;
        }

        return $this->recordDepartmentConfirmation($enrolment, $systemPhase, $actor);
    }

    private function recordDepartmentConfirmation(
        StudentEnrolment $enrolment,
        ProgrammeSemester $programmeSemester,
        ?User $actor,
        ?int $rowNumber = null,
    ): bool {
        try {
            $confirmation = $this->confirmStudyPosition->execute(
                $enrolment->fresh() ?? $enrolment,
                StudyPositionAnswerEnum::PHASE,
                $programmeSemester,
                StudyPositionSourceEnum::AUTO_DEPARTMENT_RECONCILIATION,
                $actor,
                evidence: array_filter(['row_number' => $rowNumber]),
            );
        } catch (Throwable $exception) {
            report($exception);

            return false;
        }

        return $confirmation !== null && $confirmation->source === StudyPositionSourceEnum::AUTO_DEPARTMENT_RECONCILIATION;
    }

    /**
     * The period to confirm for, when the enrolment belongs to the current calendar year.
     */
    private function currentPeriodFor(StudentEnrolment $enrolment): ?CurrentStudyPeriod
    {
        $period = $this->studyPeriods->forEnrolment($enrolment);

        if (! $period instanceof CurrentStudyPeriod
            || ! in_array((int) $enrolment->academic_calendar_id, $period->yearPeriodIds, true)) {
            return null;
        }

        return $period;
    }

    private function summaryKeyForStatus(string $status): string
    {
        return match ($status) {
            'matched' => 'matched',
            'mismatch' => 'mismatch',
            'not_enrolled' => 'notEnrolled',
            'wrong_place' => 'wrongPlace',
            default => 'invalid',
        };
    }
}
