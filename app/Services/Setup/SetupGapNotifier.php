<?php

declare(strict_types=1);

namespace App\Services\Setup;

use App\Enums\Setup\SetupGapCheckEnum;
use App\Models\Setup\SetupGap;
use App\Models\Users\User;
use App\Notifications\Setup\SetupGapsDetectedNotification;
use App\Support\Rbac\UserAccessScope;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Notification;

/**
 * Tells the people who can actually fix a gap that it exists.
 *
 * Recipients are chosen by ability, not by job title: each check names the permission its fix screen
 * requires (see SetupGapCheckEnum::permission()), and only users holding that permission hear about it.
 * A head of department who can edit department metadata is told about their own department's programme
 * gaps; someone who cannot edit assessment calendars is never told one needs dates.
 *
 * Department reach still applies on top, so a department user only ever hears about their own
 * departments, and college-wide gaps go to people who are not scoped to a department.
 *
 * Delivery is in-app only — never email. See SetupGapsDetectedNotification.
 */
class SetupGapNotifier
{
    /** @var array<string, Collection<int, User>> */
    private array $candidateCache = [];

    /**
     * Announces every open gap nobody has been told about yet. This is the nightly sweep.
     *
     * @return int the number of gaps announced
     */
    public function notifyPending(): int
    {
        return $this->announce(
            SetupGap::query()->open()->whereNull('notified_at')->get(),
        );
    }

    /**
     * Announces only these gaps, and only if they are still open and unannounced.
     *
     * A re-check triggered by someone saving a screen must not blast the whole backlog at everyone: it
     * announces what that run actually turned up, nothing else.
     *
     * @param  list<int>  $gapIds
     * @return int the number of gaps announced
     */
    public function notifyOnly(array $gapIds): int
    {
        if ($gapIds === []) {
            return 0;
        }

        return $this->announce(
            SetupGap::query()->open()->whereNull('notified_at')->whereIn('id', $gapIds)->get(),
        );
    }

    /**
     * @param  Collection<int, SetupGap>  $pending
     */
    private function announce(Collection $pending): int
    {
        if ($pending->isEmpty()) {
            return 0;
        }

        $this->candidateCache = [];

        foreach ($pending->groupBy('tenant_id') as $tenantId => $tenantGaps) {
            $this->notifyTenant((int) $tenantId, $tenantGaps);
        }

        SetupGap::query()->whereIn('id', $pending->pluck('id'))->update(['notified_at' => now()]);

        return $pending->count();
    }

    /**
     * @param  Collection<int, SetupGap>  $gaps
     */
    private function notifyTenant(int $tenantId, Collection $gaps): void
    {
        /** @var array<int, array{user: User, gaps: list<SetupGap>}> $forUser */
        $forUser = [];

        foreach ($gaps->groupBy(fn (SetupGap $gap): string => $gap->check_key->value) as $checkValue => $checkGaps) {
            $permission = SetupGapCheckEnum::from((string) $checkValue)->permission();

            foreach ($this->candidates($tenantId, $permission) as $user) {
                foreach ($checkGaps as $gap) {
                    if (! $this->canSee($user, $gap)) {
                        continue;
                    }

                    $forUser[$user->id]['user'] = $user;
                    $forUser[$user->id]['gaps'][] = $gap;
                }
            }
        }

        // One notice per person covering everything they can act on, rather than one per check.
        foreach ($forUser as $row) {
            Notification::send([$row['user']], new SetupGapsDetectedNotification(collect($row['gaps'])));
        }
    }

    private function canSee(User $user, SetupGap $gap): bool
    {
        $scope = UserAccessScope::for($user);
        $departmentId = (int) ($gap->institution_department_id ?? 0);

        if ($departmentId > 0) {
            return $scope->canReachDepartment($departmentId);
        }

        // A college-wide gap belongs to whoever is not confined to a department.
        return ! $scope->isScopedToDepartments();
    }

    /**
     * Users in this tenant holding the permission, whether granted directly or through a role.
     *
     * @return Collection<int, User>
     */
    private function candidates(int $tenantId, string $permission): Collection
    {
        $key = $tenantId.'|'.$permission;

        // UserAccessScope reads staffProfile, and can() reads roles/permissions: load them up front so a
        // scan does not fire a query per user (and does not trip lazy-loading protection).
        return $this->candidateCache[$key] ??= User::query()
            ->with(['staffProfile', 'roles.permissions', 'permissions'])
            ->where('tenant_id', $tenantId)
            ->permission($permission)
            ->get()
            ->unique('id')
            ->values();
    }
}
