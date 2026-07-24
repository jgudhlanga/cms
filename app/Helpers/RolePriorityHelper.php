<?php

namespace App\Helpers;

use App\Enums\Rbac\RoleEnum;
use App\Models\Users\User;

class RolePriorityHelper
{
    /**
     * @return list<string>
     */
    public static function priorityOrder(): array
    {
        return [
            RoleEnum::SUPER_USER->name(),
            RoleEnum::SUPER_ADMINISTRATOR->name(),
            RoleEnum::TESC->name(),
            RoleEnum::PRINCIPAL->name(),
            RoleEnum::VICE_PRINCIPAL->name(),
            RoleEnum::REGISTRAR->name(),
            RoleEnum::DEAN->name(),
            RoleEnum::BURSAR->name(),
            RoleEnum::LIBRARIAN->name(),
            RoleEnum::REGISTRY_OFFICER->name(),
            RoleEnum::HEAD_OF_DIVISION->name(),
            RoleEnum::HEAD_OF_DEPARTMENT->name(),
            RoleEnum::SENIOR_LECTURER->name(),
            RoleEnum::LECTURER_IN_CHARGE->name(),
            RoleEnum::LECTURER->name(),
            RoleEnum::SELECTION_OFFICER->name(),
            RoleEnum::IT_MANAGER->name(),
            RoleEnum::ACCOUNTANT->name(),
            RoleEnum::HR_OFFICER->name(),
            RoleEnum::ADMINISTRATIVE_OFFICER->name(),
            RoleEnum::IT_SYSTEM_ADMINISTRATOR->name(),
            RoleEnum::ACCOUNTANT_ASSISTANT->name(),
            RoleEnum::HR_OFFICER_ASSISTANT->name(),
            RoleEnum::ADMINISTRATIVE_ASSISTANT->name(),
            RoleEnum::IT_SUPPORT_TECHNICIAN->name(),
            RoleEnum::LAB_TECHNICIAN->name(),
            RoleEnum::SECURITY_OFFICER->name(),
            RoleEnum::STUDENT->name(),
        ];
    }

    public static function resolvePrimaryRoleName(User $user): string
    {
        $userRoleNames = $user->roles->pluck('name')->all();

        foreach (self::priorityOrder() as $roleName) {
            if (in_array($roleName, $userRoleNames, true)) {
                return $roleName;
            }
        }

        return $userRoleNames[0] ?? 'User';
    }
}
