<?php

namespace App\Queries\Enrolments;

use App\Enums\Shared\ClassListTypeEnum;
use App\Models\AcademicCalendars\AcademicCalendar;
use App\Models\Students\StudentApplication;
use App\Models\Students\StudentEnrolment;
use App\Models\Students\StudentSemester;
use App\Services\Students\StudentEnrolmentProgressionService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class ConfirmedStudentsQuery
{
    /**
     * Distinct FINAL-list applications with a class-allocation enrolment for started calendars in the year.
     *
     * @return array<string, int> Keys "{department_course_id}_{department_level_id}"
     */
    public function countsByCourseLevel(int $institutionDepartmentId, int $modeOfStudyId, string $calendarYear): array
    {
        return $this->countsByCourseLevelForCalendars(
            $institutionDepartmentId,
            $modeOfStudyId,
            AcademicCalendar::idsForStartedCalendarYear($calendarYear),
        );
    }

    /**
     * @param  list<int>  $academicCalendarIds
     * @return array<string, int> Keys "{department_course_id}_{department_level_id}"
     */
    public function countsByCourseLevelForCalendars(int $institutionDepartmentId, int $modeOfStudyId, array $academicCalendarIds): array
    {
        if ($academicCalendarIds === []) {
            return [];
        }

        $rows = $this->allocationBaseQuery($institutionDepartmentId, $modeOfStudyId, $academicCalendarIds)
            ->selectRaw('student_applications.department_course_id, student_applications.department_level_id, COUNT(DISTINCT student_applications.id) as total')
            ->groupBy('student_applications.department_course_id', 'student_applications.department_level_id')
            ->get();

        $lookup = [];

        foreach ($rows as $row) {
            $key = "{$row->department_course_id}_{$row->department_level_id}";
            $lookup[$key] = (int) $row->total;
        }

        return $lookup;
    }

    /**
     * FINAL-list students with one student_semester for the given academic calendars (needed for class allocation payloads).
     *
     * @param  list<int>  $academicCalendarIds
     */
    public function listForClassAllocation(
        int $institutionDepartmentId,
        int $departmentLevelId,
        int $departmentCourseId,
        int $modeOfStudyId,
        array $academicCalendarIds,
        ?int $semesterId = null,
    ): Collection {
        if ($academicCalendarIds === []) {
            return collect();
        }

        return $this->allocationBaseQuery($institutionDepartmentId, $modeOfStudyId, $academicCalendarIds, $semesterId)
            ->join('students', 'students.id', '=', 'student_applications.student_id')
            ->leftJoin('genders', 'genders.id', '=', 'students.gender_id')
            ->join('users', 'users.id', '=', 'students.user_id')
            ->where('student_applications.department_level_id', $departmentLevelId)
            ->where('student_applications.department_course_id', $departmentCourseId)
            ->select([
                'student_applications.id as student_application_id',
                'student_enrolments.id as student_enrolment_id',
                'student_semesters.id as student_semesters_id',
                'student_applications.student_id',
                'student_applications.application_tracking_number',
                'genders.title as gender_title',
                'users.first_name',
                'users.middle_name',
                'users.last_name',
            ])
            ->orderBy('users.first_name')
            ->orderBy('users.last_name')
            ->get();
    }

    /**
     * @param  list<int>  $academicCalendarIds
     */
    private function allocationBaseQuery(
        int $institutionDepartmentId,
        int $modeOfStudyId,
        array $academicCalendarIds,
        ?int $semesterId = null,
    ): Builder {
        $preferredSemesters = StudentSemester::query()
            ->selectRaw('student_enrolments.student_application_id, MAX(student_semesters.id) as student_semesters_id')
            ->join('student_enrolments', 'student_enrolments.id', '=', 'student_semesters.student_enrolment_id')
            ->whereIn('student_enrolments.academic_calendar_id', $academicCalendarIds)
            ->whereNull('student_semesters.deleted_at')
            ->whereNull('student_enrolments.deleted_at')
            ->when($semesterId !== null, fn (Builder $query) => $query->where('student_semesters.semester_id', $semesterId))
            ->groupBy('student_enrolments.student_application_id');

        return $this->baseQueryWithFinalClassList($institutionDepartmentId, $modeOfStudyId)
            ->joinSub($preferredSemesters, 'preferred_semesters', function ($join): void {
                $join->on('preferred_semesters.student_application_id', '=', 'student_applications.id');
            })
            ->join('student_semesters', 'student_semesters.id', '=', 'preferred_semesters.student_semesters_id')
            ->join('student_enrolments', 'student_enrolments.id', '=', 'student_semesters.student_enrolment_id')
            ->join('student_enrolment_statuses', 'student_enrolment_statuses.id', '=', 'student_semesters.student_enrolment_status_id')
            ->whereColumn('student_enrolments.mode_of_study_id', 'student_applications.mode_of_study_id')
            ->whereNotIn('student_enrolment_statuses.slug', StudentEnrolmentProgressionService::BLOCKING_STATUSES);
    }

    private function baseQueryWithFinalClassList(int $institutionDepartmentId, int $modeOfStudyId): Builder
    {
        return StudentApplication::query()
            ->join('class_lists', function ($join): void {
                $join->on('class_lists.student_application_id', '=', 'student_applications.id')
                    ->where('class_lists.type', ClassListTypeEnum::FINAL->value)
                    ->whereNull('class_lists.deleted_at');
            })
            ->where('student_applications.institution_department_id', $institutionDepartmentId)
            ->where('student_applications.mode_of_study_id', $modeOfStudyId)
            ->whereNull('student_applications.deleted_at');
    }
}
