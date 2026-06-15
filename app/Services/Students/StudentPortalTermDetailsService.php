<?php

declare(strict_types=1);

namespace App\Services\Students;

use App\Enums\AcademicCalendars\AcademicCalendarTypeEnum;
use App\Models\AcademicCalendars\AcademicCalendar;
use App\Models\AcademicCalendars\AcademicYearOption;
use App\Models\Students\Student;
use App\Models\Students\StudentEnrolment;
use App\Services\AcademicCalendars\ResolveAcademicYearOptionFromCalendarYear;
use App\Support\AcademicCalendars\AcademicCalendarPeriodResolver;
use Carbon\Carbon;

class StudentPortalTermDetailsService
{
    public function __construct(
        protected ResolveAcademicYearOptionFromCalendarYear $resolveAcademicYearOptionFromCalendarYear,
    ) {}

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

        if ($enrolment === null) {
            return [
                'calendarType' => AcademicCalendarTypeEnum::SEMESTER->value,
                'currentTerm' => null,
                'nextTerm' => null,
            ];
        }

        $enrolment->loadMissing([
            'studentProgram.departmentLevel.level',
            'studentProgram.intakePeriod',
            'academicCalendar',
            'academicYearOption',
        ]);

        $calendarType = $this->resolveCalendarType($enrolment);
        $currentCalendar = $this->resolveCurrentCalendar($enrolment, $calendarType);

        if ($currentCalendar === null) {
            return [
                'calendarType' => $calendarType->value,
                'currentTerm' => null,
                'nextTerm' => null,
            ];
        }

        $nextCalendar = $this->resolveNextCalendar($currentCalendar);

        return [
            'calendarType' => $calendarType->value,
            'currentTerm' => $this->mapTerm($currentCalendar),
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
                    'academicYearOption',
                    'studentProgram.departmentLevel.level',
                    'studentProgram.intakePeriod',
                ])
                ->find($enrolmentId);

            if ($enrolment instanceof StudentEnrolment) {
                return $enrolment;
            }
        }

        $student->loadMissing([
            'latestEnrolment.academicCalendar',
            'latestEnrolment.academicYearOption',
            'latestEnrolment.studentProgram.departmentLevel.level',
            'latestEnrolment.studentProgram.intakePeriod',
        ]);

        return $student->latestEnrolment;
    }

    private function resolveCalendarType(StudentEnrolment $enrolment): AcademicCalendarTypeEnum
    {
        $calendarType = $enrolment->studentProgram?->departmentLevel?->level?->calendar_type;

        if ($calendarType instanceof AcademicCalendarTypeEnum) {
            return $calendarType;
        }

        $fromString = AcademicCalendarTypeEnum::tryFrom((string) $calendarType);

        return $fromString ?? AcademicCalendarTypeEnum::SEMESTER;
    }

    private function resolveCurrentCalendar(
        StudentEnrolment $enrolment,
        AcademicCalendarTypeEnum $calendarType,
    ): ?AcademicCalendar {
        $calendarYear = $this->resolveCalendarYear($enrolment);
        $enrolmentCalendar = $enrolment->academicCalendar;

        if (
            $enrolmentCalendar instanceof AcademicCalendar
            && $enrolmentCalendar->type === $calendarType
            && (string) $enrolmentCalendar->calendar_year === $calendarYear
        ) {
            return $enrolmentCalendar;
        }

        $slug = $this->resolveYearOptionSlugForEnrolment($enrolment, $calendarType);

        if ($slug !== null) {
            $matched = $this->findCalendarByYearOptionSlug($calendarYear, $calendarType, $slug);

            if ($matched instanceof AcademicCalendar) {
                return $matched;
            }
        }

        if ($enrolmentCalendar instanceof AcademicCalendar) {
            $matchedByOpening = AcademicCalendar::query()
                ->where('calendar_year', $calendarYear)
                ->where('type', $calendarType)
                ->whereDate('opening_date', $enrolmentCalendar->opening_date)
                ->orderBy('id')
                ->first();

            if ($matchedByOpening instanceof AcademicCalendar) {
                return $matchedByOpening;
            }
        }

        return $this->resolveActiveCalendarForToday($calendarYear, $calendarType);
    }

    private function resolveCalendarYear(StudentEnrolment $enrolment): string
    {
        $fromIntake = $enrolment->studentProgram?->intakePeriod?->calendar_year;

        if (is_string($fromIntake) && $fromIntake !== '') {
            return $fromIntake;
        }

        $fromCalendar = $enrolment->academicCalendar?->calendar_year;

        if (is_string($fromCalendar) && $fromCalendar !== '') {
            return $fromCalendar;
        }

        return (string) Carbon::now()->year;
    }

    private function resolveYearOptionSlugForEnrolment(
        StudentEnrolment $enrolment,
        AcademicCalendarTypeEnum $calendarType,
    ): ?string {
        $enrolmentSlug = $enrolment->academicYearOption?->slug;

        if (is_string($enrolmentSlug) && $enrolmentSlug !== '') {
            $remapped = $this->remapYearOptionSlug($enrolmentSlug, $calendarType);

            if ($remapped !== null) {
                return $remapped;
            }
        }

        if ($enrolment->academicCalendar instanceof AcademicCalendar) {
            return AcademicCalendarPeriodResolver::academicYearOptionSlugForCalendar($enrolment->academicCalendar);
        }

        return null;
    }

    private function remapYearOptionSlug(string $enrolmentSlug, AcademicCalendarTypeEnum $calendarType): ?string
    {
        if (str_starts_with($enrolmentSlug, $calendarType->value.'-')) {
            return $enrolmentSlug;
        }

        $parts = explode('-', $enrolmentSlug);
        $suffix = end($parts);

        if (! is_numeric($suffix)) {
            return null;
        }

        return $calendarType->value.'-'.$suffix;
    }

    private function findCalendarByYearOptionSlug(
        string $calendarYear,
        AcademicCalendarTypeEnum $calendarType,
        string $slug,
    ): ?AcademicCalendar {
        $calendars = AcademicCalendar::query()
            ->where('calendar_year', $calendarYear)
            ->where('type', $calendarType)
            ->orderBy('opening_date')
            ->orderBy('id')
            ->get();

        foreach ($calendars as $calendar) {
            if (AcademicCalendarPeriodResolver::academicYearOptionSlugForCalendar($calendar) === $slug) {
                return $calendar;
            }
        }

        return null;
    }

    private function resolveActiveCalendarForToday(
        string $calendarYear,
        AcademicCalendarTypeEnum $calendarType,
    ): ?AcademicCalendar {
        $optionId = $this->resolveAcademicYearOptionFromCalendarYear->resolveForCalendarType($calendarYear, $calendarType);

        if ($optionId === null) {
            return null;
        }

        $slug = AcademicYearOption::query()->whereKey($optionId)->value('slug');

        if (! is_string($slug) || $slug === '') {
            return null;
        }

        return $this->findCalendarByYearOptionSlug($calendarYear, $calendarType, $slug);
    }

    private function resolveNextCalendar(AcademicCalendar $current): ?AcademicCalendar
    {
        $nextInYear = AcademicCalendar::query()
            ->where('calendar_year', $current->calendar_year)
            ->where('type', $current->type)
            ->whereDate('opening_date', '>', $current->opening_date)
            ->orderBy('opening_date')
            ->orderBy('id')
            ->first();

        if ($nextInYear instanceof AcademicCalendar) {
            return $nextInYear;
        }

        return AcademicCalendar::query()
            ->where('type', $current->type)
            ->where('calendar_year', '>', $current->calendar_year)
            ->orderBy('calendar_year')
            ->orderBy('opening_date')
            ->orderBy('id')
            ->first();
    }

    /**
     * @return array{
     *     label: string,
     *     calendarYear: string,
     *     openingDate: string,
     *     closingDate: string|null
     * }
     */
    private function mapTerm(AcademicCalendar $calendar): array
    {
        $label = $this->resolveYearOptionLabel($calendar);

        return [
            'label' => $label,
            'calendarYear' => (string) $calendar->calendar_year,
            'openingDate' => Carbon::parse($calendar->opening_date)->toDateString(),
            'closingDate' => $calendar->closing_date !== null
                ? Carbon::parse($calendar->closing_date)->toDateString()
                : null,
        ];
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
