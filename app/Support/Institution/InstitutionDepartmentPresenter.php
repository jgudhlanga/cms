<?php

namespace App\Support\Institution;

use App\Enums\Rbac\RoleEnum;
use App\Models\Institution\InstitutionDepartment;

final class InstitutionDepartmentPresenter
{
    public static function headOfDepartmentName(InstitutionDepartment $department): ?string
    {
        $department->loadMissing(['staff.user.roles']);

        foreach ($department->staff as $staff) {
            if ($staff->user?->roles->contains(
                fn ($role): bool => $role->slug === RoleEnum::HEAD_OF_DEPARTMENT->value,
            )) {
                return $staff->user->full_name;
            }
        }

        return null;
    }

    /**
     * @return list<string>
     */
    public static function levelsOffered(InstitutionDepartment $department): array
    {
        $department->loadMissing(['departmentLevels.level']);

        return $department->departmentLevels
            ->map(fn ($departmentLevel): ?string => $departmentLevel->level?->name)
            ->filter()
            ->unique()
            ->values()
            ->all();
    }
}
