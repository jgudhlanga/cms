<?php

namespace App\Support\Acl;

class PermissionRegistry
{
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
