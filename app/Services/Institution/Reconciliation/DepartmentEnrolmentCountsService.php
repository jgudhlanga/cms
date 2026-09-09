<?php

declare(strict_types=1);

namespace App\Services\Institution\Reconciliation;

use App\Enums\AcademicCalendars\AcademicCalendarTypeEnum;
use App\Models\AcademicCalendars\Semester;
use App\Models\Institution\InstitutionDepartment;
use App\Models\Students\StudentEnrolment;
use App\Support\AcademicCalendars\AcademicCalendarPeriodResolver;
use Illuminate\Database\Eloquent\Builder;

class DepartmentEnrolmentCountsService
{
    /**
     * @return array{
     *     enrolledThisYear: int,
     *     enrolledThisPeriod: int,
     *     calendarYear: int,
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
