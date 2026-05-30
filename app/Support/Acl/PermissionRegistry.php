<?php

namespace App\Support\Acl;

use App\Enums\Shared\ModuleEnum;
use Illuminate\Support\Str;

class PermissionRegistry
{
    public static function moduleTitleForGroupKey(string $groupKey): string
    {
        foreach (ModuleEnum::cases() as $case) {
            if (Str::slug($case->value) === $groupKey) {
                return $case->value;
            }
        }

        return Str::headline($groupKey);
    }

    /**
     * @return array<string, array<int, string>>
     */
    public static function grouped(): array
    {
        /** @var array<string, array<int, string>> $groups */
        $groups = config('permissions.groups', []);

        return $groups;
    }

    /**
     * @return array<int, string>
     */
    public static function allValues(): array
    {
        $allValues = [];

        foreach (self::grouped() as $permissions) {
            foreach ($permissions as $permission) {
                $allValues[] = $permission;
            }
        }

        return array_values(array_unique($allValues));
    }

    public static function exists(string $permission): bool
    {
        return in_array($permission, self::allValues(), true);
    }
}
