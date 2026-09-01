<?php

declare(strict_types=1);

namespace App\Support\Institution;

use App\Enums\Rbac\RoleEnum;
use App\Models\Institution\InstitutionDepartment;

class DepartmentStaffRoles
{
    /**
     * @return list<string>
     */
    public static function allowedSlugsFor(InstitutionDepartment $institutionDepartment): array
    {
        return self::assignableSlugs();
    }

    /**
     * @return list<string>
     */
    public static function assignableSlugs(): array
    {
        return array_values(array_map(
            static fn (RoleEnum $role): string => $role->value,
            array_filter(
                RoleEnum::cases(),
                static fn (RoleEnum $role): bool => $role->group() !== 'super-user',
            ),
        ));
    }
}
