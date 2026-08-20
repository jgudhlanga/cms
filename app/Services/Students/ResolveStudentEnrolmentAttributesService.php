<?php

namespace App\Services\Students;

use App\Enums\AcademicCalendars\AcademicCalendarTypeEnum;
use App\Exceptions\Students\StudentEnrolmentResolutionException;
use App\Models\AcademicCalendars\AcademicCalendar;
use App\Models\AcademicCalendars\Semester;
use App\Models\Students\StudentApplication;
use App\Models\Students\StudentEnrolmentStatus;
use Carbon\CarbonInterface;
use Illuminate\Support\Carbon;

class ResolveStudentEnrolmentAttributesService
{
    public const ACADEMIC_YEAR_OPTION_SLUG_SEMESTER_1 = 'semester-1';

    public const ACADEMIC_YEAR_OPTION_SLUG_SEMESTER_2 = 'semester-2';

    public const STUDENT_ENROLMENT_STATUS_SLUG_ACTIVE = 'active';

    public const STUDENT_ENROLMENT_STATUS_SLUG_COMPLETED = 'completed';

    public function __construct(
        protected StudentEnrolmentProgressionService $progression,
    ) {}

    /**
     * @return array{academic_calendar_id: int, semester_id: int, student_enrolment_status_id: int}
     */
    public function resolve(int $studentId, int $studentApplicationId, ?CarbonInterface $asOf = null): array
    {
        $asOf = $asOf ?? Carbon::now((string) config('app.timezone'));
        $studentApplication = $this->resolveStudentApplication($studentApplicationId);

        return [
            'academic_calendar_id' => $this->resolveAcademicCalendarId($studentApplication, $asOf),
            'semester_id' => $this->resolveSemesterId($studentApplication),
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

        $calendarQuery = AcademicCalendar::query()->where('type', $calendarType);

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
            "No academic calendar was found for type \"{$calendarType}\"."
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

    private function resolveSemesterId(StudentApplication $studentApplication): int
    {
        $prefix = $this->resolveCalendarType($studentApplication)->value;
        $options = Semester::query()
            ->where('slug', 'like', "{$prefix}-%")
            ->get()
            ->sortBy(function (Semester $option): int {
                $parts = explode('-', (string) $option->slug);

                return (int) end($parts);
            })
            ->values();

        if ($options->isEmpty()) {
            throw new StudentEnrolmentResolutionException(
                "No academic year options were found for calendar type \"{$prefix}\"."
            );
        }

        $existingPhaseCount = $this->progression->existingPhaseCount($studentApplication);
        $optionIndex = min($existingPhaseCount, $options->count() - 1);
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
}
