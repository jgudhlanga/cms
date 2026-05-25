<?php

namespace App\Policies\Students;

use App\Models\Students\Student;
use App\Models\Users\User;

class StudentPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('viewAny:students');
    }

    public function view(User $user, Student $student): bool
    {
        return $user->can('viewAny:students') || $user->can('view:students');
    }

    public function create(User $user): bool
    {
        return $user->can('create:students');
    }

    public function update(User $user, Student $student): bool
    {
        return $user->can('update:students', $student);
    }

    public function delete(User $user, Student $student): bool
    {
        return $user->can('delete:students', $student);
    }

    public function restore(User $user, Student $student): bool
    {
        return $user->can('restore:students', $student);
    }

    public function forceDelete(User $user, Student $student): bool
    {
        return $user->can('forceDelete:students', $student);
    }
}
