<?php

namespace App\Services\Assessments;

use App\Enums\Assessments\MissingMarksNotificationTierEnum;
use App\Models\Institution\AssessmentCalendar\AssessmentCalendar;
use App\Models\Institution\AssessmentType;
use Carbon\Carbon;
use Carbon\CarbonInterface;

class AssessmentCalendarWindowService
{
    public function __construct(private readonly MissingMarksQueryService $missingMarksQuery) {}

    /**
     * @param  list<int>|null  $modeIds
     * @return list<array<string, mixed>>
     */
    public function windowsForAcademicCalendar(int $academicCalendarId, ?array $modeIds = null): array
    {
        $today = now()->startOfDay();
        $windows = [];

        $calendars = AssessmentCalendar::query()
            ->where('academic_calendar_id', $academicCalendarId)
            ->with('assessmentType')
            ->orderBy('end_date')
            ->get();

        foreach ($calendars as $calendar) {
            $assessmentType = $calendar->assessmentType;

            if (! $assessmentType instanceof AssessmentType) {
                continue;
            }

            $typeModeIds = array_values(array_filter(
                array_map('intval', $assessmentType->modes_of_study ?? []),
                static fn (int $id): bool => $id > 0,
            ));

            if ($modeIds !== null && array_intersect($modeIds, $typeModeIds) === []) {
                continue;
            }

            $endDate = $calendar->end_date;
            $startDate = $calendar->start_date;
            $daysRemaining = $endDate instanceof CarbonInterface
                ? (int) $today->diffInDays(Carbon::parse($endDate)->startOfDay(), false)
                : null;

            $missingRows = $this->missingMarksQuery->forCalendarForCurrentUser($calendar);
            $missingByClassId = [];

            foreach ($missingRows as $row) {
                $classId = (int) $row['academicCalendarClassId'];
                $missingByClassId[$classId] = ($missingByClassId[$classId] ?? 0) + 1;
            }

            $windows[] = [
                'assessmentCalendarId' => (int) $calendar->id,
                'assessmentTypeId' => (int) $assessmentType->id,
                'assessmentTypeName' => (string) $assessmentType->name,
                'modeIds' => $typeModeIds,
                'startDate' => $startDate?->format('Y-m-d'),
                'endDate' => $endDate?->format('Y-m-d'),
                'firstNotificationDate' => $calendar->first_notification_date?->format('Y-m-d'),
                'secondNotificationDate' => $calendar->second_notification_date?->format('Y-m-d'),
                'dueNotificationDate' => $calendar->due_notification_date?->format('Y-m-d'),
                'firstNotificationDaysBefore' => $calendar->daysBeforeFor(MissingMarksNotificationTierEnum::First),
                'secondNotificationDaysBefore' => $calendar->daysBeforeFor(MissingMarksNotificationTierEnum::Second),
                'dueNotificationDaysBefore' => $calendar->daysBeforeFor(MissingMarksNotificationTierEnum::Due),
                'daysRemaining' => $daysRemaining,
                'isOpen' => $startDate instanceof CarbonInterface
                    && $endDate instanceof CarbonInterface
                    && $today->between(
                        Carbon::parse($startDate)->startOfDay(),
                        Carbon::parse($endDate)->endOfDay(),
                    ),
                'isInNotificationWindow' => $calendar->isInNotificationWindow($today),
                'missingCount' => count($missingRows),
                'missingByClassId' => $missingByClassId,
                'severity' => $daysRemaining === null
                    ? 'info'
                    : $this->severityForDaysRemaining($daysRemaining, $calendar),
            ];
        }

        usort($windows, function (array $left, array $right): int {
            if ($left['isOpen'] !== $right['isOpen']) {
                return $right['isOpen'] <=> $left['isOpen'];
            }

            return ($left['daysRemaining'] ?? 9999) <=> ($right['daysRemaining'] ?? 9999);
        });

        return $windows;
    }

    /**
     * @return array<int, list<array<string, mixed>>>
     */
    public function windowsByModeId(int $academicCalendarId): array
    {
        $byMode = [];

        foreach ($this->windowsForAcademicCalendar($academicCalendarId) as $window) {
            $publicWindow = $window;
            unset($publicWindow['modeIds'], $publicWindow['missingByClassId']);

            foreach ($window['modeIds'] as $modeId) {
                $classWindow = $publicWindow;
                $byMode[$modeId][] = $classWindow;
            }
        }

        return $byMode;
    }

    /**
     * @param  list<array<string, mixed>>  $windows
     * @return list<array<string, mixed>>
     */
    public function windowsForClass(array $windows, int $classId, int $modeOfStudyId): array
    {
        $matched = [];

        foreach ($windows as $window) {
            if (! in_array($modeOfStudyId, $window['modeIds'] ?? [], true)) {
                continue;
            }

            $classWindow = $window;
            $classWindow['missingCount'] = (int) ($window['missingByClassId'][$classId] ?? 0);
            unset($classWindow['modeIds'], $classWindow['missingByClassId']);
            $matched[] = $classWindow;
        }

        return $matched;
    }

    public function severityForDaysRemaining(int $daysRemaining, AssessmentCalendar $calendar): string
    {
        if ($daysRemaining <= $calendar->daysBeforeFor(MissingMarksNotificationTierEnum::Due)) {
            return 'critical';
        }

        if ($daysRemaining <= $calendar->daysBeforeFor(MissingMarksNotificationTierEnum::Second)) {
            return 'warning';
        }

        return 'info';
    }
}
