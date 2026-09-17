<?php

declare(strict_types=1);

namespace App\Services\Institution\Reconciliation;

use App\Enums\Students\StudyPositionStateEnum;
use App\Models\Institution\InstitutionDepartment;
use App\Models\Institution\ProgrammeSemester;
use App\Models\Students\StudentEnrolment;
use App\Models\Students\StudentStudyPositionConfirmation;
use App\Services\Institution\ProgrammeSemesterResolver;
use App\Services\Students\StudyPosition\StudyPositionScope;
use App\Support\Institution\ProgrammeSemesterNameFormatter;
use Illuminate\Database\Eloquent\Builder;

/**
 * The row-level listing behind each study-position tile on the department reconciliation page:
 * every enrolment in the department in the given state, for the bulk "confirm on record" tool.
 */
class DepartmentStudyPositionListService
{
    /** Rows returned in one response; a department this size is better split by level/course. */
    private const MAX_ROWS = 1000;

    public function __construct(
        private readonly StudyPositionScope $scope,
        private readonly ProgrammeSemesterResolver $programmeSemesterResolver,
    ) {}

    /**
     * @return array{
     *     periodLabel: string|null,
     *     total: int,
     *     truncated: bool,
     *     rows: list<array<string, mixed>>,
     * }
     */
    public function list(
        InstitutionDepartment $department,
        StudyPositionStateEnum $state,
        ?int $modeOfStudyId = null,
        ?int $departmentLevelId = null,
        ?int $departmentCourseId = null,
    ): array {
        $query = $this->baseQuery($department, $modeOfStudyId, $departmentLevelId, $departmentCourseId);
        $this->scope->whereState($query, $state);

        $total = (clone $query)->count();

        $enrolments = $query
            ->with([
                'student.user',
                'institutionDepartment.department',
                'departmentLevel.level',
                'departmentCourse.course',
                'modeOfStudy',
                'studentSemesters.semester',
                'studentSemesters.programmeSemester',
                'studyPositionConfirmations' => fn ($q) => $q
                    ->withoutGlobalScopes()
                    ->forPeriods($this->scope->currentPeriodIds())
                    ->with('programmeSemester'),
            ])
            ->orderBy('student_enrolments.id')
            ->limit(self::MAX_ROWS)
            ->get();

        $rows = $enrolments
            ->map(fn (StudentEnrolment $enrolment): array => $this->rowFor($enrolment))
            ->values()
            ->all();

        return [
            'periodLabel' => $this->scope->periodLabel(),
            'total' => $total,
            'truncated' => $total > count($rows),
            'rows' => $rows,
        ];
    }

    /**
     * @return Builder<StudentEnrolment>
     */
    private function baseQuery(
        InstitutionDepartment $department,
        ?int $modeOfStudyId,
        ?int $departmentLevelId,
        ?int $departmentCourseId,
    ): Builder {
        $query = $this->scope->constrain(
            StudentEnrolment::query()->where('student_enrolments.institution_department_id', $department->id),
        );

        return $query
            ->when($modeOfStudyId !== null, fn (Builder $q) => $q->where('student_enrolments.mode_of_study_id', $modeOfStudyId))
            ->when($departmentLevelId !== null, fn (Builder $q) => $q->where('student_enrolments.department_level_id', $departmentLevelId))
            ->when($departmentCourseId !== null, fn (Builder $q) => $q->where('student_enrolments.department_course_id', $departmentCourseId));
    }

    /**
     * @return array<string, mixed>
     */
    private function rowFor(StudentEnrolment $enrolment): array
    {
        $levelName = $enrolment->departmentLevel?->level?->name;
        $systemRow = $enrolment->currentStudentSemester();
        $systemPhase = $systemRow?->programmeSemester
            ?? ($systemRow !== null ? $this->programmeSemesterResolver->programmeSemesterForStudentSemester($systemRow) : null);

        /** @var StudentStudyPositionConfirmation|null $confirmation */
        $confirmation = $enrolment->studyPositionConfirmations->first();

        return [
            'enrolmentId' => (int) $enrolment->id,
            'studentId' => (int) $enrolment->student_id,
            'studentNumber' => $enrolment->student?->student_number,
            'studentName' => $enrolment->student?->user?->full_name,
            'department' => $enrolment->institutionDepartment?->department?->name,
            'level' => $levelName,
            'course' => $enrolment->departmentCourse?->course?->name,
            'modeOfStudy' => $enrolment->modeOfStudy?->name,
            'systemPhase' => $systemPhase instanceof ProgrammeSemester
                ? ProgrammeSemesterNameFormatter::qualifiedName($levelName, $systemPhase->name)
                : null,
            'canConfirmOnRecord' => $systemPhase instanceof ProgrammeSemester,
            'answer' => $confirmation?->answer->label(),
            'answerPhase' => $confirmation?->programmeSemester instanceof ProgrammeSemester
                ? ProgrammeSemesterNameFormatter::qualifiedName($levelName, $confirmation->programmeSemester->name)
                : null,
            'source' => $confirmation?->source->label(),
            'syncNote' => $confirmation?->sync_note,
        ];
    }
}
