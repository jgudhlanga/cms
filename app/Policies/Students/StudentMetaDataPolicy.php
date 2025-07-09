<?php

namespace App\Policies\Students;

use App\Enums\Acl\PermissionEnum;
use App\Models\Users\User;

class StudentMetaDataPolicy
{
	public function manageStudentMetadata(User $user): bool
	{
		return  $user->can(PermissionEnum::MANAGE_STUDENTS_METADATA);
	}
}
