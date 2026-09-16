<?php

declare(strict_types=1);

namespace App\Services\Setup;

use App\Enums\Setup\SetupGapCheckEnum;
use App\Models\Setup\SetupGap;
use App\Models\Users\User;
use App\Support\Rbac\UserAccessScope;
use Illuminate\Database\Eloquent\Builder;

/**
 * Who may see which gaps. The header count and the panel both go through here, so nobody is shown a
 * count that includes gaps their panel then hides.
 *
 * Two rules, both required:
 *  - ability: a gap is only shown to someone who holds the permission its fix screen needs, so people
 *    are never told about problems they cannot act on;
 *  - reach: a department user sees their own departments only, and college-wide gaps (those with no
 *    department, such as an unset assessment calendar) belong to people who are not scoped to one.
 */
class SetupGapVisibility
{
    public function visibleTo(User $user): Builder
    {
        // Scope and ability checks read these; the header count runs on every request, so they are
        // loaded once rather than lazily per lookup.
        $user->loadMissing(['staffProfile', 'roles.permissions', 'permissions']);

        $allowedChecks = $this->allowedCheckKeys($user);

        $query = SetupGap::query()
            ->open()
            ->where('tenant_id', $user->tenant_id);

        if ($allowedChecks === []) {
            return $query->whereRaw('1 = 0');
        }

        $query->whereIn('check_key', $allowedChecks);

        $scope = UserAccessScope::for($user);

        if ($scope->isScopedToDepartments()) {
            $departmentIds = $scope->departmentIds() ?? [];

            return $departmentIds === []
                ? $query->whereRaw('1 = 0')
                : $query->whereIn('institution_department_id', $departmentIds);
        }

        return $query;
    }

    public function openCountFor(User $user): int
    {
        return $this->visibleTo($user)->count();
    }

    /**
     * The checks this user is allowed to hear about, by the permission each one's fix requires.
     *
     * @return list<string>
     */
    private function allowedCheckKeys(User $user): array
    {
        $allowed = [];

        foreach (SetupGapCheckEnum::cases() as $check) {
            if ($user->can($check->permission())) {
                $allowed[] = $check->value;
            }
        }

        return $allowed;
    }
}
