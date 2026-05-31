<?php

declare(strict_types=1);

namespace App\Services\Students;

use App\Models\AcademicCalendars\AcademicCalendar;
use App\Models\Students\Student;
use App\Models\Students\StudentEnrolment;
use App\Support\AcademicCalendars\AcademicCalendarPeriodResolver;
use Carbon\Carbon;

class StudentPortalTermDetailsService
{
    /**
     * @param  array<string, mixed>  $activeSemester
     * @return array{
     *     currentTerm: array<string, mixed>|null,
     *     nextTerm: array<string, mixed>|null
     * }
     */
    public function build(Student $student, array $activeSemester): array
    {
        $enrolment = $this->resolveEnrolment($student, $activeSemester);

        if ($enrolment === null || ! $enrolment->academicCalendar instanceof AcademicCalendar) {
            return [
                'currentTerm' => null,
                'nextTerm' => null,
            ];
        }

        $calendar = $enrolment->academicCalendar;
        $nextCalendar = $this->resolveNextCalendar($calendar);

        return [
            'currentTerm' => $this->mapTerm($calendar, $enrolment->academicYearOption?->name),
            'nextTerm' => $nextCalendar !== null ? $this->mapTerm($nextCalendar, null) : null,
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
                ->with(['academicCalendar', 'academicYearOption'])
                ->find($enrolmentId);

            if ($enrolment instanceof StudentEnrolment) {
                return $enrolment;
            }
        }

        $student->loadMissing(['latestEnrolment.academicCalendar', 'latestEnrolment.academicYearOption']);

        return $student->latestEnrolment;
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
    private function mapTerm(AcademicCalendar $calendar, ?string $yearOptionName): array
    {
        $label = $yearOptionName !== null && $yearOptionName !== ''
            ? $yearOptionName
            : AcademicCalendarPeriodResolver::displayPeriodLabel($calendar);

        return [
            'label' => $label,
            'calendarYear' => (string) $calendar->calendar_year,
            'openingDate' => Carbon::parse($calendar->opening_date)->toDateString(),
            'closingDate' => $calendar->closing_date !== null
                ? Carbon::parse($calendar->closing_date)->toDateString()
                : null,
        ];
    }
}
