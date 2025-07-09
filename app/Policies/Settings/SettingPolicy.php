<?php

namespace App\Policies\Settings;

use App\Enums\Acl\PermissionEnum;
use App\Models\Users\User;

class SettingPolicy
{
	public function viewSettings(User $user): bool
	{
		return $user->can(PermissionEnum::ROOT_MANAGE) || $user->can(PermissionEnum::VIEW_SETTINGS);

	}

	public function createSettings(User $user): bool
	{
		return $user->can(PermissionEnum::ROOT_MANAGE) || $user->can(PermissionEnum::CREATE_SETTINGS);
	}

	public function updateSettings(User $user): bool
	{
		return $user->can(PermissionEnum::ROOT_MANAGE) || $user->can(PermissionEnum::UPDATE_SETTINGS);
	}

	public function deleteSettings(User $user): bool
	{

		return $user->can(PermissionEnum::ROOT_MANAGE) || $user->can(PermissionEnum::DELETE_SETTINGS);
	}

	public function restoreSettings(User $user): bool
	{
		return $user->can(PermissionEnum::ROOT_MANAGE) || $user->can(PermissionEnum::RESTORE_SETTINGS);
	}

	public function forceDeleteSettings(User $user): bool
	{
		return $user->can(PermissionEnum::ROOT_MANAGE) || $user->can(PermissionEnum::FORCE_DELETE_SETTINGS);
	}

	public function importSettings(User $user): bool
	{
		return $user->can(PermissionEnum::ROOT_MANAGE) || $user->can(PermissionEnum::IMPORT_SETTINGS);
	}

	public function exportSettings(User $user): bool
	{
		return $user->can(PermissionEnum::ROOT_MANAGE) || $user->can(PermissionEnum::EXPORT_SETTINGS);
	}
}
