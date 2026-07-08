<?php

namespace App\Services\HMS;

use App\Models\Students\Student;
use App\Models\Students\StudentEnrolment;
use App\Services\Students\StudentPortalTermDetailsService;
use Carbon\CarbonInterface;

class HostelApplicationSemesterService
{
    public const BLOCKER_NO_RUNNING_SEMESTER = 'no_running_semester';

    public const BLOCKER_CALENDAR_YEAR_MISSING = 'calendar_year_missing';

    public function __construct(
        protected StudentPortalTermDetailsService $termDetailsService,
    ) {}

    public function resolveCalendarYear(StudentEnrolment $enrolment): ?string
    {
        $calendarYear = $enrolment->studentApplication?->intakePeriod?->calendar_year;

        if ($calendarYear === null || $calendarYear === '') {
            return null;
        }

        return (string) $calendarYear;
    }

    /**
     * @return array{
     *     success: bool,
     *     blocker: string|null,
     *     checkIn: string|null,
     *     checkOut: string|null,
     *     label: string|null,
     * }
     */
    public function datesForApplication(Student $student, ?CarbonInterface $asOf = null): array
    {
        $enrolment = $student->latestEnrolment;

        if ($enrolment === null) {
            return $this->failure(self::BLOCKER_CALENDAR_YEAR_MISSING);
        }

        $calendarYear = $this->resolveCalendarYear($enrolment);

        if ($calendarYear === null) {
            return $this->failure(self::BLOCKER_CALENDAR_YEAR_MISSING);
        }

        $termDetails = $this->termDetailsService->build($student, [
            'studentEnrolmentId' => $enrolment->id,
        ]);

        $term = $termDetails['currentTerm'] ?? $termDetails['nextTerm'];

        if ($term === null || empty($term['openingDate']) || empty($term['closingDate'])) {
            return $this->failure(self::BLOCKER_NO_RUNNING_SEMESTER);
        }

        return [
            'success' => true,
            'blocker' => null,
            'checkIn' => $term['openingDate'],
            'checkOut' => $term['closingDate'],
            'label' => $term['label'],
        ];
    }

    /**
     * @return array{success: bool, blocker: string|null, checkIn: null, checkOut: null, label: null}
     */
    private function failure(string $blocker): array
    {
        return [
            'success' => false,
            'blocker' => $blocker,
            'checkIn' => null,
            'checkOut' => null,
            'label' => null,
        ];
    }
}
