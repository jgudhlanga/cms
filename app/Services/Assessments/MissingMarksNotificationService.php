<?php

namespace App\Services\Assessments;

use App\Enums\Assessments\MissingMarksNotificationTierEnum;
use App\Enums\Rbac\RoleEnum;
use App\Models\Assessments\MissingMarksEscalation;
use App\Models\Institution\AssessmentCalendar\AssessmentCalendar;
use App\Models\Institution\AssessmentCalendar\AssessmentCalendarNotificationDispatch;
use App\Models\Users\User;
use App\Notifications\Assessments\MissingMarksNotification;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Notification;

class MissingMarksNotificationService
{
    public function __construct(private readonly MissingMarksQueryService $missingMarksQuery) {}

    public function dispatchDueTiers(): int
    {
        $sent = 0;
        $today = now()->startOfDay();

        $calendars = AssessmentCalendar::query()
            ->with('assessmentType')
            ->get();

        foreach ($calendars as $calendar) {
            $tier = $calendar->matchingTier($today);

            if (! $tier instanceof MissingMarksNotificationTierEnum) {
                continue;
            }

            if ($this->alreadyDispatched($calendar, $tier)) {
                continue;
            }

            if ($this->dispatchForCalendar($calendar, $tier, recordDispatch: true)) {
                $sent++;
            }
        }

        return $sent;
    }

    public function remindLecturers(AssessmentCalendar $calendar): bool
    {
        $calendar->loadMissing('assessmentType');

        return $this->dispatchForCalendar(
            $calendar,
            MissingMarksNotificationTierEnum::First,
            recordDispatch: false,
            lecturersOnly: true,
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

    private function dispatchForCalendar(
        AssessmentCalendar $calendar,
        MissingMarksNotificationTierEnum $tier,
        bool $recordDispatch,
        bool $lecturersOnly = false,
    ): bool {
        $rows = $this->missingMarksQuery->forCalendar($calendar);

        if ($rows === []) {
            return false;
        }

        $grouped = $this->missingMarksQuery->groupedByClassModule($rows);
        $recipients = collect();

        if ($tier->includesLecturers() || $lecturersOnly) {
            $recipients = $recipients->merge($this->lecturerUsers($rows));
        }

        if (! $lecturersOnly && $tier->includesVicePrincipal()) {
            $recipients = $recipients->merge($this->vicePrincipalUsers((int) $calendar->tenant_id));
        }

        $recipients = $recipients->unique('id')->values();

        if ($recipients->isEmpty()) {
            return false;
        }

        Notification::send(
            $recipients,
            new MissingMarksNotification($calendar, $tier, count($rows), $grouped),
        );

        if ($recordDispatch) {
            AssessmentCalendarNotificationDispatch::query()->create([
                'tenant_id' => $calendar->tenant_id,
                'assessment_calendar_id' => $calendar->id,
                'tier' => $tier,
                'sent_at' => now(),
            ]);
        }

        return true;
    }

    private function alreadyDispatched(AssessmentCalendar $calendar, MissingMarksNotificationTierEnum $tier): bool
    {
        return AssessmentCalendarNotificationDispatch::query()
            ->where('assessment_calendar_id', $calendar->id)
            ->where('tier', $tier->value)
            ->exists();
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
