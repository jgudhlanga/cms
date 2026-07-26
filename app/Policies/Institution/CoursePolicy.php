<?php

namespace App\Policies\Institution;

use App\Models\Institution\Course;
use App\Models\Users\User;

class CoursePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('viewAny:courses');
    }

    public function view(User $user, Course $course): bool
    {
        return $user->can('viewAny:courses') || $user->can('view:courses');
    }

    public function create(User $user): bool
    {
        return $user->can('create:courses');
    }

    public function update(User $user, Course $course): bool
    {
        return $user->can('update:courses');
    }

    public function delete(User $user, Course $course): bool
    {
        return $user->can('delete:courses');
    }

    public function restore(User $user, Course $course): bool
    {
        return $user->can('restore:courses');
    }

    public function forceDelete(User $user, Course $course): bool
    {
        return $user->can('forceDelete:courses');
    }
}
