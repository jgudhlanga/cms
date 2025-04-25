<?php

namespace App\Policies\Shared;

use App\Enums\PermissionEnum;
use App\Models\Shared\BankDetail;
use App\Models\Users\User;

class BankDetailPolicy
{
	public function viewAny(User $user): bool
	{
		return $user->can(PermissionEnum::VIEW_ANY_BANK_DETAILS);
	}

	public function view(User $user, BankDetail $bankDetail): bool
	{
		return $user->can(PermissionEnum::VIEW_ANY_BANK_DETAILS)
			|| $user->can(PermissionEnum::VIEW_BANK_DETAILS);
	}

	public function create(User $user): bool
	{
		return $user->can(PermissionEnum::CREATE_BANK_DETAILS);
	}

	public function update(User $user, BankDetail $bankDetail): bool
	{
		return $user->can(PermissionEnum::UPDATE_BANK_DETAILS, $bankDetail);
	}

	public function delete(User $user, BankDetail $bankDetail): bool
	{
		return $user->can(PermissionEnum::DELETE_BANK_DETAILS, $bankDetail);
	}

	public function restore(User $user, BankDetail $bankDetail): bool
	{
		return $user->can(PermissionEnum::RESTORE_BANK_DETAILS, $bankDetail);
	}

	public function forceDelete(User $user, BankDetail $bankDetail): bool
	{
		return $user->can(PermissionEnum::FORCE_DELETE_BANK_DETAILS, $bankDetail);
	}
}
