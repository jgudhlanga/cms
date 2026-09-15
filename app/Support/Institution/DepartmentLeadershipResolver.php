<?php

namespace App\Support\Institution;

use App\Enums\Rbac\RoleEnum;
use App\Models\Users\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

/**
 * Finds the people who lead academic work: heads of department are the department's staff holding the
 * head-of-department role (the same rule InstitutionDepartmentPresenter uses for display).
 */
final class DepartmentLeadershipResolver
{
    /**
     * @return Collection<int, User>
     */
    public function headsOfDepartment(int $institutionDepartmentId): Collection
    {
        if ($institutionDepartmentId < 1) {
            return collect();
        }

        return User::query()
            ->whereHas(
                'staffProfile.institutionDepartments',
                fn (Builder $query): Builder => $query->where('institution_departments.id', $institutionDepartmentId),
            )
            ->whereHas('roles', fn (Builder $query): Builder => $query->where('slug', RoleEnum::HEAD_OF_DEPARTMENT->value))
            ->get();
    }

    /**
     * @return Collection<int, User>
     */
    public function vicePrincipalsAcademics(int $tenantId): Collection
    {
        return User::query()
            ->where('tenant_id', $tenantId)
            ->whereHas('roles', fn (Builder $query): Builder => $query->where('slug', RoleEnum::VICE_PRINCIPAL->value))
            ->get();
    }
}
