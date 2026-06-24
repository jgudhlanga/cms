<?php

namespace App\Services\Students;

use App\Enums\AcademicCalendars\AcademicCalendarTypeEnum;
use App\Exceptions\Students\StudentEnrolmentResolutionException;
use App\Models\AcademicCalendars\AcademicCalendar;
use App\Models\AcademicCalendars\AcademicYearOption;
use App\Models\Students\StudentEnrolment;
use App\Models\Students\StudentEnrolmentStatus;
use App\Models\Students\StudentApplication;
use Carbon\CarbonInterface;
use Illuminate\Support\Carbon;

class ResolveStudentEnrolmentAttributesService
{
    public const ACADEMIC_YEAR_OPTION_SLUG_SEMESTER_1 = 'semester-1';

    public const ACADEMIC_YEAR_OPTION_SLUG_SEMESTER_2 = 'semester-2';

    public const STUDENT_ENROLMENT_STATUS_SLUG_ACTIVE = 'active';

    public const STUDENT_ENROLMENT_STATUS_SLUG_COMPLETED = 'completed';

    /**
     * @return array{academic_calendar_id: int, academic_year_option_id: int, student_enrolment_status_id: int}
     */
    public function resolve(int $studentId, int $studentApplicationId, ?CarbonInterface $asOf = null): array
    {
        $asOf = $asOf ?? Carbon::now((string) config('app.timezone'));
        $studentApplication = $this->resolveStudentApplication($studentApplicationId);

        return [
            'academic_calendar_id' => $this->resolveAcademicCalendarId($studentApplication, $asOf),
            'academic_year_option_id' => $this->resolveAcademicYearOptionId($studentId, $studentApplication),
            'student_enrolment_status_id' => $this->resolveActiveStudentEnrolmentStatusId(),
        ];
    }

    private function resolveStudentApplication(int $studentApplicationId): StudentApplication
    {
        $studentApplication = StudentApplication::query()
            ->with(['departmentLevel.level', 'intakePeriod'])
            ->find($studentApplicationId);

        if ($studentApplication === null) {
            throw new StudentEnrolmentResolutionException("Student program with id \"{$studentApplicationId}\" was not found.");
        }

        return $studentApplication;
    }

    private function resolveAcademicCalendarId(StudentApplication $studentApplication, CarbonInterface $asOf): int
    {
        $today = $asOf->copy()->timezone((string) config('app.timezone'))->toDateString();
        $calendarType = $this->resolveCalendarType($studentApplication)->value;
        $calendarYear = $this->resolveCalendarYear($studentApplication);

        $calendarQuery = AcademicCalendar::query()
            ->where('type', $calendarType)
            ->where('calendar_year', $calendarYear);

        $current = (clone $calendarQuery)
            ->whereDate('opening_date', '<=', $today)
            ->whereDate('closing_date', '>=', $today)
            ->orderBy('opening_date')
            ->first();

        if ($current !== null) {
            return (int) $current->id;
        }

        $future = (clone $calendarQuery)
            ->whereDate('opening_date', '>', $today)
            ->orderBy('opening_date')
            ->first();

        if ($future !== null) {
            return (int) $future->id;
        }

        throw new StudentEnrolmentResolutionException(
            "No academic calendar was found for calendar year \"{$calendarYear}\" and type \"{$calendarType}\"."
        );
    }

    private function resolveCalendarType(StudentApplication $studentApplication): AcademicCalendarTypeEnum
    {
        $calendarType = $studentApplication->departmentLevel?->level?->calendar_type;

        if (! $calendarType instanceof AcademicCalendarTypeEnum) {
            throw new StudentEnrolmentResolutionException(
                "Calendar type is missing for student program id \"{$studentApplication->id}\"."
            );
        }

        return $calendarType;
    }

    private function resolveCalendarYear(StudentApplication $studentApplication): string
    {
        $calendarYear = $studentApplication->intakePeriod?->calendar_year;

        if ($calendarYear === null || $calendarYear === '') {
            throw new StudentEnrolmentResolutionException(
                "Calendar year is missing on intake period for student program id \"{$studentApplication->id}\"."
            );
        }

        return $calendarYear;
    }

    private function resolveAcademicYearOptionId(int $studentId, StudentApplication $studentApplication): int
    {
        $prefix = $this->resolveCalendarType($studentApplication)->value;
        $options = AcademicYearOption::query()
            ->where('slug', 'like', "{$prefix}-%")
            ->get()
            ->sortBy(function (AcademicYearOption $option): int {
                $parts = explode('-', (string) $option->slug);

                return (int) end($parts);
            })
            ->values();

        if ($options->isEmpty()) {
            throw new StudentEnrolmentResolutionException(
                "No academic year options were found for calendar type \"{$prefix}\"."
            );
        }

        $completedEnrolments = $this->completedEnrolmentCount($studentId, $studentApplication);
        $optionIndex = min($completedEnrolments, $options->count() - 1);
        $option = $options->get($optionIndex);

        if ($option === null) {
            throw new StudentEnrolmentResolutionException(
                "Academic year option could not be resolved for calendar type \"{$prefix}\"."
            );
        }

        return (int) $option->id;
    }

    private function resolveActiveStudentEnrolmentStatusId(): int
    {
        $status = StudentEnrolmentStatus::query()
            ->where('slug', self::STUDENT_ENROLMENT_STATUS_SLUG_ACTIVE)
            ->first();

        if ($status === null) {
            throw new StudentEnrolmentResolutionException(
                'Student enrolment status with slug "active" is missing.'
            );
        }

        return (int) $status->id;
    }

    private function completedEnrolmentCount(int $studentId, StudentApplication $studentApplication): int
    {
        return (int) StudentEnrolment::query()
            ->where('student_enrolments.student_id', $studentId)
            ->where('student_enrolments.institution_department_id', $studentApplication->institution_department_id)
            ->where('student_enrolments.department_level_id', $studentApplication->department_level_id)
            ->where('student_enrolments.department_course_id', $studentApplication->department_course_id)
            ->whereHas('studentEnrolmentStatus', function ($query): void {
                $query->where('slug', self::STUDENT_ENROLMENT_STATUS_SLUG_COMPLETED);
            })
            ->count();
    }
}
