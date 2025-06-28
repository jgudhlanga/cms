<?php

namespace App\Policies\Shared;

use App\Enums\Shared\PermissionEnum;
use App\Models\Shared\NextOfKin;
use App\Models\Users\User;

class NextOfKinPolicy
{
	public function viewAny(User $user): bool
	{
		return $user->can(PermissionEnum::VIEW_ANY_NEXT_OF_KINS);
	}

	public function view(User $user, NextOfKin $nextOfKin): bool
	{
		return $user->can(PermissionEnum::VIEW_ANY_NEXT_OF_KINS)
			|| $user->can(PermissionEnum::VIEW_NEXT_OF_KINS);
	}

	public function create(User $user): bool
	{
		return $user->can(PermissionEnum::CREATE_NEXT_OF_KINS);
	}

	public function update(User $user, NextOfKin $nextOfKin): bool
	{
		return $user->can(PermissionEnum::UPDATE_NEXT_OF_KINS, $nextOfKin);
	}

	public function delete(User $user, NextOfKin $nextOfKin): bool
	{
		return $user->can(PermissionEnum::DELETE_NEXT_OF_KINS, $nextOfKin);
	}

	public function restore(User $user, NextOfKin $nextOfKin): bool
	{
		return $user->can(PermissionEnum::RESTORE_NEXT_OF_KINS, $nextOfKin);
	}

	public function forceDelete(User $user, NextOfKin $nextOfKin): bool
	{
		return $user->can(PermissionEnum::FORCE_DELETE_NEXT_OF_KINS, $nextOfKin);
	}
}
