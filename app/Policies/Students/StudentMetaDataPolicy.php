<?php

namespace App\Policies\Students;

use App\Models\Users\User;

class StudentMetaDataPolicy
{
    public function manageStudentMetadata(User $user): bool
    {
        return $user->can('manageStudentMetadata:admin');
    }
}
