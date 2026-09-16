<?php

declare(strict_types=1);

namespace App\Services\Institution\Reconciliation;

use App\Enums\AcademicCalendars\AcademicCalendarTypeEnum;
use App\Enums\Students\StudyPositionStateEnum;
use App\Models\AcademicCalendars\Semester;
use App\Models\Institution\InstitutionDepartment;
use App\Models\Students\StudentEnrolment;
use App\Services\Students\StudyPosition\CurrentStudyPeriod;
use App\Services\Students\StudyPosition\CurrentStudyPeriodResolver;
use App\Services\Students\StudyPosition\StudyPositionScope;
use App\Support\AcademicCalendars\AcademicCalendarPeriodResolver;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

class DepartmentEnrolmentCountsService
{
    public function __construct(
        private readonly StudyPositionScope $studyPositionScope,
        private readonly CurrentStudyPeriodResolver $studyPeriods,
    ) {}

    /**
     * @return array{
     *     enrolledThisYear: int,
     *     enrolledThisPeriod: int,
     *     calendarYear: int,
     *     studyPosition: array{periodLabel: string, inScope: int, confirmed: int, unconfirmed: int, followUp: int, needsReview: int}|null,
     * }
     */
    public function resolve(
        InstitutionDepartment $department,
        int $calendarYear,
        ?int $modeOfStudyId = null,
        ?int $departmentLevelId = null,
        ?int $departmentCourseId = null,
    ): array {
        $yearQuery = $this->baseQuery(
            $department,
            $calendarYear,
            $modeOfStudyId,
            $departmentLevelId,
            $departmentCourseId,
        );

        $enrolledThisYear = (clone $yearQuery)->count();

        $periodSemesterIds = $this->currentPeriodSemesterIds((string) $calendarYear);

        $enrolledThisPeriod = $periodSemesterIds === []
            ? 0
            : (clone $yearQuery)
                ->where(function (Builder $query) use ($periodSemesterIds): void {
                    $query->whereIn('student_enrolments.semester_id', $periodSemesterIds)
                        ->orWhereHas('studentSemesters', function (Builder $semesterQuery) use ($periodSemesterIds): void {
                            $semesterQuery->whereIn('semester_id', $periodSemesterIds);
                        });
                })
                ->count();

        return [
            'enrolledThisYear' => $enrolledThisYear,
            'enrolledThisPeriod' => $enrolledThisPeriod,
            'calendarYear' => $calendarYear,
            'studyPosition' => $this->studyPositionCounts($yearQuery, (string) $calendarYear),
        ];
    }

    /**
     * How far this department's students are with confirming their study position. Only the
     * current calendar year has a period to confirm, so other years report nothing.
     *
     * @param  Builder<StudentEnrolment>  $yearQuery
     * @return array{periodLabel: string, inScope: int, confirmed: int, unconfirmed: int, followUp: int, needsReview: int}|null
     */
    private function studyPositionCounts(Builder $yearQuery, string $calendarYear): ?array
    {
        $labels = array_values(array_unique(array_map(
            static fn (CurrentStudyPeriod $period): string => $period->label,
            array_filter(
                $this->studyPeriods->all(),
                static fn (CurrentStudyPeriod $period): bool => $period->calendarYear === $calendarYear,
            ),
        )));

        if ($labels === []) {
            return null;
        }

        $scope = $this->studyPositionScope;

        $counts = $scope->leftJoinConfirmation($scope->constrain(clone $yearQuery))
            ->select(DB::raw($scope->stateCaseSql().' as study_position_bucket'))
            ->selectRaw('count(distinct student_enrolments.id) as aggregate_count')
            ->groupBy('study_position_bucket')
            ->pluck('aggregate_count', 'study_position_bucket');

        $count = static fn (StudyPositionStateEnum $state): int => (int) ($counts[$state->value] ?? 0);

        return [
            'periodLabel' => implode(' / ', $labels),
            'inScope' => (int) $counts->sum(),
            'confirmed' => $count(StudyPositionStateEnum::CONFIRMED),
            'unconfirmed' => $count(StudyPositionStateEnum::UNCONFIRMED),
            'followUp' => $count(StudyPositionStateEnum::FOLLOW_UP),
            'needsReview' => $count(StudyPositionStateEnum::NEEDS_REVIEW),
        ];
    }

    /**
     * @return Builder<StudentEnrolment>
     */
    private function baseQuery(
        InstitutionDepartment $department,
        int $calendarYear,
        ?int $modeOfStudyId,
        ?int $departmentLevelId,
        ?int $departmentCourseId,
    ): Builder {
        return StudentEnrolment::query()
            ->where('student_enrolments.institution_department_id', $department->id)
            ->whereHas('academicCalendar', function (Builder $query) use ($calendarYear): void {
                $query->where('calendar_year', (string) $calendarYear);
            })
            ->when(
                $modeOfStudyId !== null,
                fn (Builder $query) => $query->where('student_enrolments.mode_of_study_id', $modeOfStudyId),
            )
            ->when(
                $departmentLevelId !== null,
                fn (Builder $query) => $query->where('student_enrolments.department_level_id', $departmentLevelId),
            )
            ->when(
                $departmentCourseId !== null,
                fn (Builder $query) => $query->where('student_enrolments.department_course_id', $departmentCourseId),
            );
    }

    /**
     * @return list<int>
     */
    private function currentPeriodSemesterIds(string $calendarYear): array
    {
        $ids = [];

        foreach (AcademicCalendarTypeEnum::cases() as $type) {
            $slug = AcademicCalendarPeriodResolver::currentSemesterSlugForYear($calendarYear, $type);
            $id = Semester::query()->where('slug', $slug)->value('id');

            if ($id !== null) {
                $ids[] = (int) $id;
            }
        }

        return array_values(array_unique($ids));
    }
}
