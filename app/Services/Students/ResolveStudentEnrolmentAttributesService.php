<?php

namespace App\Services\Students;

use App\Enums\AcademicCalendars\AcademicCalendarTypeEnum;
use App\Exceptions\Students\StudentEnrolmentResolutionException;
use App\Models\AcademicCalendars\AcademicCalendar;
use App\Models\AcademicCalendars\Semester;
use App\Models\Students\StudentApplication;
use App\Models\Students\StudentEnrolmentStatus;
use App\Support\AcademicCalendars\AcademicCalendarPeriodResolver;
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
        $academicCalendarId = $this->resolveAcademicCalendarId($studentApplication, $asOf);

        return [
            'academic_calendar_id' => $academicCalendarId,
            'semester_id' => $this->resolveSemesterId($studentApplication, $academicCalendarId),
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

    private function resolveSemesterId(StudentApplication $studentApplication, ?int $academicCalendarId = null): int
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

        // A first enrolment starts in the calendar period it is actually created in, not at
        // phase 1 of the year — an August intake begins at semester-2, not semester-1.
        $optionIndex = $existingPhaseCount === 0
            ? $this->startingPhaseIndex($academicCalendarId, $options->count() - 1)
            : min($existingPhaseCount, $options->count() - 1);

        $option = $options->get($optionIndex);

        if ($option === null) {
            throw new StudentEnrolmentResolutionException(
                "Academic year option could not be resolved for calendar type \"{$prefix}\"."
            );
        }

        return (int) $option->id;
    }

    /**
     * Zero-based index of the calendar period the enrolment is being created in.
     */
    private function startingPhaseIndex(?int $academicCalendarId, int $maxIndex): int
    {
        if ($academicCalendarId === null) {
            return 0;
        }

        $calendar = AcademicCalendar::query()->find($academicCalendarId);

        if ($calendar === null) {
            return 0;
        }

        $slug = AcademicCalendarPeriodResolver::semesterSlugForCalendar($calendar);
        $parts = explode('-', $slug);
        $ordinal = (int) end($parts);

        return max(0, min($ordinal - 1, $maxIndex));
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
