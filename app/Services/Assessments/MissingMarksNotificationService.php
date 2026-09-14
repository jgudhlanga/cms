<?php

namespace App\Services\Assessments;

use App\Enums\Assessments\AssessmentWindowEventEnum;
use App\Enums\Assessments\MissingMarksNotificationTierEnum;
use App\Enums\Rbac\RoleEnum;
use App\Models\Assessments\MissingMarksEscalation;
use App\Models\Institution\AssessmentCalendar\AssessmentCalendar;
use App\Models\Institution\AssessmentCalendar\AssessmentCalendarNotificationDispatch;
use App\Models\Institution\AssessmentCalendar\AssessmentWindowNotification;
use App\Models\Institution\AssessmentCalendar\DepartmentAssessmentCalendar;
use App\Models\Institution\InstitutionDepartment;
use App\Models\Users\User;
use App\Notifications\Assessments\AssessmentWindowClosedNotification;
use App\Notifications\Assessments\AssessmentWindowOpenedNotification;
use App\Notifications\Assessments\MissingMarksNotification;
use App\Support\Institution\DepartmentLeadershipResolver;
use Carbon\Carbon;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Notification;

/**
 * Daily coursework notifications.
 *
 * - College scope (VP Academics) follows the global calendar dates.
 * - Department scope follows the department's own window when it has one: lecturers get the first and
 *   second reminders, the HOD the second and due reminders, and each department is told once when its
 *   window opens and once when it closes with marks still missing.
 *
 * Tiers catch up: the highest tier whose date has passed (up to the end date) is sent if it has not been
 * sent for that scope, so a missed scheduler run never silently skips a reminder.
 */
class MissingMarksNotificationService
{
    private const int CLOSED_NOTICE_GRACE_DAYS = 7;

    public function __construct(
        private readonly MissingMarksQueryService $missingMarksQuery,
        private readonly DepartmentLeadershipResolver $leadership,
    ) {}

    public function dispatchDueTiers(): int
    {
        $sent = 0;
        $today = now()->startOfDay();

        $calendars = AssessmentCalendar::query()
            ->with(['assessmentType', 'departmentCalendars'])
            ->get();

        foreach ($calendars as $calendar) {
            if ($this->dispatchCollegeTier($calendar, $today)) {
                $sent++;
            }

            foreach ($this->missingMarksQuery->institutionDepartmentIdsForCalendar($calendar) as $institutionDepartmentId) {
                $sent += $this->dispatchDepartmentNotifications($calendar, $institutionDepartmentId, $today);
            }
        }

        return $sent;
    }

    /**
     * @param  list<int>|null  $institutionDepartmentIds  Null reminds every department's lecturers.
     */
    public function remindLecturers(AssessmentCalendar $calendar, ?array $institutionDepartmentIds = null): bool
    {
        $calendar->loadMissing('assessmentType');
        $rows = $this->missingMarksQuery->forCalendar($calendar, $institutionDepartmentIds);

        return $this->sendTier(
            $calendar,
            MissingMarksNotificationTierEnum::First,
            $rows,
            $this->lecturerUsers($rows),
        );
    }

    public function escalateToPrincipal(AssessmentCalendar $calendar, User $escalatedBy, ?string $notes = null): bool
    {
        $calendar->loadMissing('assessmentType');

        if ($this->hasEscalated($calendar)) {
            return false;
        }

        $rows = $this->missingMarksQuery->forCalendar($calendar);

        if ($rows === []) {
            return false;
        }

        $grouped = $this->missingMarksQuery->groupedByClassModule($rows);

        MissingMarksEscalation::query()->create([
            'tenant_id' => $calendar->tenant_id,
            'assessment_calendar_id' => $calendar->id,
            'escalated_by' => $escalatedBy->id,
            'notes' => $notes,
            'snapshot' => $grouped,
        ]);

        activity()
            ->performedOn($calendar)
            ->causedBy($escalatedBy)
            ->withProperties([
                'notes' => $notes,
                'incomplete_count' => count($rows),
            ])
            ->log('missing_marks_escalated');

        $recipients = $this->principalUsers((int) $calendar->tenant_id);

        if ($recipients->isNotEmpty()) {
            Notification::send(
                $recipients,
                new MissingMarksNotification(
                    $calendar,
                    MissingMarksNotificationTierEnum::Due,
                    count($rows),
                    $grouped,
                    isEscalation: true,
                    escalationNotes: $notes,
                ),
            );
        }

        return true;
    }

    public function hasEscalated(AssessmentCalendar $calendar): bool
    {
        return MissingMarksEscalation::query()
            ->where('assessment_calendar_id', $calendar->id)
            ->exists();
    }

    public function lastDispatchedTier(AssessmentCalendar $calendar): ?MissingMarksNotificationTierEnum
    {
        $dispatch = AssessmentCalendarNotificationDispatch::query()
            ->where('assessment_calendar_id', $calendar->id)
            ->orderByDesc('sent_at')
            ->first();

        return $dispatch?->tier;
    }

    /**
     * The highest reminder tier whose notification date has arrived (on or before the end date) and that has
     * not been sent yet for the scope.
     *
     * @param  callable(MissingMarksNotificationTierEnum): int  $daysBefore
     * @param  callable(MissingMarksNotificationTierEnum): bool  $alreadySent
     */
    public function dueTier(CarbonInterface $endDate, callable $daysBefore, CarbonInterface $today, callable $alreadySent): ?MissingMarksNotificationTierEnum
    {
        $end = Carbon::parse($endDate)->startOfDay();
        $day = Carbon::parse($today)->startOfDay();

        if ($day->gt($end)) {
            return null;
        }

        $highestDue = null;

        foreach (MissingMarksNotificationTierEnum::cases() as $tier) {
            if ($end->copy()->subDays($daysBefore($tier))->lte($day)) {
                $highestDue = $tier;
            }
        }

        return $highestDue !== null && ! $alreadySent($highestDue) ? $highestDue : null;
    }

    private function dispatchCollegeTier(AssessmentCalendar $calendar, Carbon $today): bool
    {
        if ($calendar->end_date === null) {
            return false;
        }

        $tier = $this->dueTier(
            $calendar->end_date,
            fn (MissingMarksNotificationTierEnum $tier): int => $calendar->daysBeforeFor($tier),
            $today,
            fn (MissingMarksNotificationTierEnum $tier): bool => $this->alreadyDispatched($calendar, $tier, AssessmentCalendarNotificationDispatch::SCOPE_GLOBAL),
        );

        // The first reminder is for lecturers only, so the college scope has nobody to tell.
        if ($tier === null || ! $tier->includesVicePrincipal()) {
            return false;
        }

        $rows = $this->missingMarksQuery->forCalendar($calendar);

        return $this->sendTier(
            $calendar,
            $tier,
            $rows,
            $this->vicePrincipalUsers((int) $calendar->tenant_id),
            recordScope: AssessmentCalendarNotificationDispatch::SCOPE_GLOBAL,
        );
    }

    private function dispatchDepartmentNotifications(AssessmentCalendar $calendar, int $institutionDepartmentId, Carbon $today): int
    {
        /** @var DepartmentAssessmentCalendar|null $departmentCalendar */
        $departmentCalendar = $calendar->departmentCalendars->firstWhere('institution_department_id', $institutionDepartmentId);
        $departmentCalendar?->setRelation('assessmentCalendar', $calendar);

        $windowStart = Carbon::parse($departmentCalendar?->start_date ?? $calendar->start_date)->startOfDay();
        $windowEnd = Carbon::parse($departmentCalendar?->end_date ?? $calendar->end_date)->startOfDay();
        $daysSource = $departmentCalendar ?? $calendar;
        $scope = AssessmentCalendarNotificationDispatch::departmentScope($institutionDepartmentId);
        $assessmentName = (string) ($calendar->assessmentType?->name ?? __('trans.assessment_type'));
        $departmentName = $this->departmentName($institutionDepartmentId);
        $endDateLabel = $windowEnd->format('d M Y');
        $sent = 0;

        $rows = null;
        $missingRows = function () use (&$rows, $calendar, $institutionDepartmentId): array {
            return $rows ??= $this->missingMarksQuery->forCalendar($calendar, [$institutionDepartmentId]);
        };

        if ($today->betweenIncluded($windowStart, $windowEnd) && ! $this->windowEventSent($calendar, $institutionDepartmentId, AssessmentWindowEventEnum::Opened)) {
            $lecturers = $this->lecturerUsers($missingRows());

            if ($lecturers->isNotEmpty()) {
                Notification::send($lecturers, new AssessmentWindowOpenedNotification(
                    $assessmentName,
                    $departmentName,
                    $endDateLabel,
                    $departmentCalendar?->notes,
                ));
                $sent++;
            }

            $this->recordWindowEvent($calendar, $institutionDepartmentId, AssessmentWindowEventEnum::Opened);
        }

        $tier = $this->dueTier(
            $windowEnd,
            fn (MissingMarksNotificationTierEnum $tier): int => $daysSource->daysBeforeFor($tier),
            $today,
            fn (MissingMarksNotificationTierEnum $tier): bool => $this->alreadyDispatched($calendar, $tier, $scope),
        );

        if ($tier !== null) {
            $recipients = collect();

            if ($tier->includesLecturers()) {
                $recipients = $recipients->merge($this->lecturerUsers($missingRows()));
            }

            if ($tier !== MissingMarksNotificationTierEnum::First) {
                $recipients = $recipients->merge($this->leadership->headsOfDepartment($institutionDepartmentId));
            }

            if ($this->sendTier(
                $calendar,
                $tier,
                $missingRows(),
                $recipients,
                recordScope: $scope,
                institutionDepartmentId: $institutionDepartmentId,
                departmentName: $departmentName,
                effectiveEndDate: $windowEnd->toDateString(),
            )) {
                $sent++;
            }
        }

        if (
            $today->gt($windowEnd)
            && $today->lte($windowEnd->copy()->addDays(self::CLOSED_NOTICE_GRACE_DAYS))
            && ! $this->windowEventSent($calendar, $institutionDepartmentId, AssessmentWindowEventEnum::Closed)
        ) {
            $closedRows = $missingRows();

            if ($closedRows !== []) {
                $lecturers = $this->lecturerUsers($closedRows);
                $leaders = $this->leadership->headsOfDepartment($institutionDepartmentId)
                    ->reject(fn (User $leader): bool => $lecturers->contains('id', $leader->id));

                if ($lecturers->isNotEmpty()) {
                    Notification::send($lecturers, new AssessmentWindowClosedNotification($assessmentName, $departmentName, $endDateLabel, count($closedRows)));
                }

                if ($leaders->isNotEmpty()) {
                    Notification::send($leaders, new AssessmentWindowClosedNotification($assessmentName, $departmentName, $endDateLabel, count($closedRows), forLeadership: true));
                }

                if ($lecturers->isNotEmpty() || $leaders->isNotEmpty()) {
                    $sent++;
                }
            }

            $this->recordWindowEvent($calendar, $institutionDepartmentId, AssessmentWindowEventEnum::Closed);
        }

        return $sent;
    }

    /**
     * @param  list<array<string, mixed>>  $rows
     * @param  Collection<int, User>  $recipients
     */
    private function sendTier(
        AssessmentCalendar $calendar,
        MissingMarksNotificationTierEnum $tier,
        array $rows,
        Collection $recipients,
        ?string $recordScope = null,
        ?int $institutionDepartmentId = null,
        ?string $departmentName = null,
        ?string $effectiveEndDate = null,
    ): bool {
        $recipients = $recipients->unique('id')->values();

        if ($rows === [] || $recipients->isEmpty()) {
            return false;
        }

        Notification::send($recipients, new MissingMarksNotification(
            $calendar,
            $tier,
            count($rows),
            $this->missingMarksQuery->groupedByClassModule($rows),
            departmentName: $departmentName,
            effectiveEndDate: $effectiveEndDate,
        ));

        if ($recordScope !== null) {
            AssessmentCalendarNotificationDispatch::query()->create([
                'tenant_id' => $calendar->tenant_id,
                'assessment_calendar_id' => $calendar->id,
                'institution_department_id' => $institutionDepartmentId,
                'scope_key' => $recordScope,
                'tier' => $tier,
                'sent_at' => now(),
            ]);
        }

        return true;
    }

    private function alreadyDispatched(AssessmentCalendar $calendar, MissingMarksNotificationTierEnum $tier, string $scope): bool
    {
        return AssessmentCalendarNotificationDispatch::query()
            ->where('assessment_calendar_id', $calendar->id)
            ->where('scope_key', $scope)
            ->where('tier', $tier->value)
            ->exists();
    }

    private function windowEventSent(AssessmentCalendar $calendar, int $institutionDepartmentId, AssessmentWindowEventEnum $event): bool
    {
        return AssessmentWindowNotification::query()
            ->where('assessment_calendar_id', $calendar->id)
            ->where('institution_department_id', $institutionDepartmentId)
            ->where('event', $event->value)
            ->exists();
    }

    private function recordWindowEvent(AssessmentCalendar $calendar, int $institutionDepartmentId, AssessmentWindowEventEnum $event): void
    {
        AssessmentWindowNotification::query()->create([
            'tenant_id' => $calendar->tenant_id,
            'assessment_calendar_id' => $calendar->id,
            'institution_department_id' => $institutionDepartmentId,
            'event' => $event->value,
            'sent_at' => now(),
        ]);
    }

    private function departmentName(int $institutionDepartmentId): string
    {
        $department = InstitutionDepartment::query()->with('department')->find($institutionDepartmentId);

        return (string) ($department?->department?->name ?? $department?->department_code ?? '');
    }

    /**
     * @param  list<array<string, mixed>>  $rows
     * @return Collection<int, User>
     */
    private function lecturerUsers(array $rows): Collection
    {
        $userIds = collect($rows)
            ->pluck('lecturerUserIds')
            ->flatten()
            ->filter()
            ->map(fn ($id): int => (int) $id)
            ->unique()
            ->values()
            ->all();

        if ($userIds === []) {
            return collect();
        }

        return User::query()->whereIn('id', $userIds)->get();
    }

    /**
     * @return Collection<int, User>
     */
    private function vicePrincipalUsers(int $tenantId): Collection
    {
        return User::query()
            ->where('tenant_id', $tenantId)
            ->role(RoleEnum::VICE_PRINCIPAL->name())
            ->get();
    }

    /**
     * @return Collection<int, User>
     */
    private function principalUsers(int $tenantId): Collection
    {
        return User::query()
            ->where('tenant_id', $tenantId)
            ->role(RoleEnum::PRINCIPAL->name())
            ->get();
    }
}
