<?php

declare(strict_types=1);

namespace App\Services\Students;

use App\Enums\AcademicCalendars\AcademicCalendarTypeEnum;
use App\Models\AcademicCalendars\AcademicCalendar;
use App\Models\AcademicCalendars\AcademicYearOption;
use App\Models\Students\Student;
use App\Models\Students\StudentApplication;
use App\Models\Students\StudentEnrolment;
use App\Support\AcademicCalendars\AcademicCalendarPeriodResolver;
use Carbon\Carbon;

class StudentPortalTermDetailsService
{
    /**
     * @param  array<string, mixed>  $activeSemester
     * @return array{
     *     calendarType: string,
     *     currentTerm: array<string, mixed>|null,
     *     nextTerm: array<string, mixed>|null
     * }
     */
    public function build(Student $student, array $activeSemester): array
    {
        $enrolment = $this->resolveEnrolment($student, $activeSemester);
        $context = $this->resolveCalendarContext($student, $enrolment);

        if ($context === null) {
            return [
                'calendarType' => AcademicCalendarTypeEnum::SEMESTER->value,
                'currentTerm' => null,
                'nextTerm' => null,
            ];
        }

        $calendarType = $context['calendarType'];
        $calendarYear = $context['calendarYear'];

        $currentCalendar = AcademicCalendar::resolveCurrentPeriodForDate($calendarYear, $calendarType);

        if ($currentCalendar === null) {
            $upcomingCalendar = AcademicCalendar::resolveUpcomingPeriodForDate($calendarYear, $calendarType);

            return [
                'calendarType' => $calendarType->value,
                'currentTerm' => null,
                'nextTerm' => $upcomingCalendar !== null ? $this->mapTerm($upcomingCalendar) : null,
            ];
        }

        $nextCalendar = AcademicCalendar::resolveNextPeriodAfter($currentCalendar);

        return [
            'calendarType' => $calendarType->value,
            'currentTerm' => $this->mapTerm($currentCalendar, $nextCalendar),
            'nextTerm' => $nextCalendar !== null ? $this->mapTerm($nextCalendar) : null,
        ];
    }

    /**
     * @param  array<string, mixed>  $activeSemester
     */
    private function resolveEnrolment(Student $student, array $activeSemester): ?StudentEnrolment
    {
        $enrolmentId = $activeSemester['studentEnrolmentId'] ?? null;

        if ($enrolmentId !== null) {
            $enrolment = StudentEnrolment::query()
                ->with([
                    'academicCalendar',
                    'studentApplication.departmentLevel.level',
                    'studentApplication.intakePeriod',
                ])
                ->find($enrolmentId);

            if ($enrolment instanceof StudentEnrolment) {
                return $enrolment;
            }
        }

        $student->loadMissing([
            'latestEnrolment.academicCalendar',
            'latestEnrolment.studentApplication.departmentLevel.level',
            'latestEnrolment.studentApplication.intakePeriod',
        ]);

        return $student->latestEnrolment;
    }

    /**
     * @return array{calendarType: AcademicCalendarTypeEnum, calendarYear: string}|null
     */
    private function resolveCalendarContext(Student $student, ?StudentEnrolment $enrolment): ?array
    {
        if ($enrolment instanceof StudentEnrolment) {
            $enrolment->loadMissing([
                'studentApplication.departmentLevel.level',
                'studentApplication.intakePeriod',
                'academicCalendar',
            ]);

            $calendarYear = $this->resolveCalendarYearFromApplication(
                $enrolment->studentApplication,
                $enrolment->academicCalendar?->calendar_year,
            );

            if ($calendarYear === null) {
                return null;
            }

            return [
                'calendarType' => $this->resolveCalendarTypeFromApplication($enrolment->studentApplication),
                'calendarYear' => $calendarYear,
            ];
        }

        $student->loadMissing([
            'latestApplication.departmentLevel.level',
            'latestApplication.intakePeriod',
        ]);

        $application = $student->latestApplication;

        if (! $application instanceof StudentApplication) {
            return null;
        }

        $calendarYear = $this->resolveCalendarYearFromApplication($application);

        if ($calendarYear === null) {
            return null;
        }

        return [
            'calendarType' => $this->resolveCalendarTypeFromApplication($application),
            'calendarYear' => $calendarYear,
        ];
    }

    private function resolveCalendarTypeFromApplication(?StudentApplication $application): AcademicCalendarTypeEnum
    {
        $calendarType = $application?->departmentLevel?->level?->calendar_type;

        if ($calendarType instanceof AcademicCalendarTypeEnum) {
            return $calendarType;
        }

        $fromString = AcademicCalendarTypeEnum::tryFrom((string) $calendarType);

        return $fromString ?? AcademicCalendarTypeEnum::SEMESTER;
    }

    private function resolveCalendarYearFromApplication(
        ?StudentApplication $application,
        string|null $fallbackCalendarYear = null,
    ): ?string {
        $fromIntake = $application?->intakePeriod?->calendar_year;

        if (is_string($fromIntake) && $fromIntake !== '') {
            return $fromIntake;
        }

        if (is_string($fallbackCalendarYear) && $fallbackCalendarYear !== '') {
            return $fallbackCalendarYear;
        }

        return null;
    }

    /**
     * @return array{
     *     label: string,
     *     calendarYear: string,
     *     openingDate: string,
     *     closingDate: string|null
     * }
     */
    private function mapTerm(AcademicCalendar $calendar, ?AcademicCalendar $nextPeriod = null): array
    {
        $label = $this->resolveYearOptionLabel($calendar);

        return [
            'label' => $label,
            'calendarYear' => (string) $calendar->calendar_year,
            'openingDate' => Carbon::parse($calendar->opening_date)->toDateString(),
            'closingDate' => $this->resolveEffectiveClosingDate($calendar, $nextPeriod),
        ];
    }

    private function resolveEffectiveClosingDate(AcademicCalendar $calendar, ?AcademicCalendar $nextPeriod): ?string
    {
        if ($nextPeriod instanceof AcademicCalendar) {
            return Carbon::parse($nextPeriod->opening_date)->subDay()->toDateString();
        }

        if ($calendar->closing_date !== null) {
            return Carbon::parse($calendar->closing_date)->toDateString();
        }

        return null;
    }

    private function resolveYearOptionLabel(AcademicCalendar $calendar): string
    {
        $slug = AcademicCalendarPeriodResolver::academicYearOptionSlugForCalendar($calendar);

        $name = AcademicYearOption::query()->where('slug', $slug)->value('name');

        if (is_string($name) && $name !== '') {
            return $name;
        }

        return AcademicCalendarPeriodResolver::displayPeriodLabel($calendar);
    }
}
