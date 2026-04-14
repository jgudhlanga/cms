<?php

namespace App\Services\Students;

use App\Exceptions\Students\StudentEnrolmentResolutionException;
use App\Models\AcademicCalendars\AcademicCalendar;
use App\Models\AcademicCalendars\AcademicYearOption;
use App\Models\Students\StudentEnrolment;
use App\Models\Students\StudentEnrolmentStatus;
use App\Models\Students\StudentProgram;
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
    public function resolve(int $studentId, int $studentProgramId, ?CarbonInterface $asOf = null): array
    {
        $asOf = $asOf ?? Carbon::now((string) config('app.timezone'));

        return [
            'academic_calendar_id' => $this->resolveAcademicCalendarId($asOf),
            'academic_year_option_id' => $this->resolveAcademicYearOptionId($studentId, $studentProgramId),
            'student_enrolment_status_id' => $this->resolveActiveStudentEnrolmentStatusId(),
        ];
    }

    private function resolveAcademicCalendarId(CarbonInterface $asOf): int
    {
        $today = $asOf->copy()->timezone((string) config('app.timezone'))->toDateString();

        $current = AcademicCalendar::query()
            ->whereDate('opening_date', '<=', $today)
            ->whereDate('closing_date', '>=', $today)
            ->orderBy('opening_date')
            ->first();

        if ($current !== null) {
            return (int) $current->id;
        }

        $future = AcademicCalendar::query()
            ->whereDate('opening_date', '>', $today)
            ->orderBy('opening_date')
            ->first();

        if ($future !== null) {
            return (int) $future->id;
        }

        throw new StudentEnrolmentResolutionException(
            'No academic calendar covers the current date and no future academic calendar was found.'
        );
    }

    private function resolveAcademicYearOptionId(int $studentId, int $studentProgramId): int
    {
        $slug = $this->studentHasCompletedEnrolment($studentId, $studentProgramId)
            ? self::ACADEMIC_YEAR_OPTION_SLUG_SEMESTER_2
            : self::ACADEMIC_YEAR_OPTION_SLUG_SEMESTER_1;

        $option = AcademicYearOption::query()->where('slug', $slug)->first();

        if ($option === null) {
            throw new StudentEnrolmentResolutionException(
                "Academic year option with slug \"{$slug}\" is missing."
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

    private function studentHasCompletedEnrolment(int $studentId, int $studentProgramId): bool
    {
        $currentProgram = StudentProgram::query()->find($studentProgramId);

        if ($currentProgram === null) {
            return false;
        }

        return StudentEnrolment::query()
            ->where('student_enrolments.student_id', $studentId)
            ->where('student_enrolments.institution_department_id', $currentProgram->institution_department_id)
            ->where('student_enrolments.department_level_id', $currentProgram->department_level_id)
            ->where('student_enrolments.department_course_id', $currentProgram->department_course_id)
            ->whereHas('studentEnrolmentStatus', function ($query): void {
                $query->where('slug', self::STUDENT_ENROLMENT_STATUS_SLUG_COMPLETED);
            })
            ->exists();
    }
}
