<?php

namespace App\Policies\Students;

use App\Models\Students\StudentEnrolmentStatus;
use App\Models\Users\User;

class StudentEnrolmentStatusPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('viewAny:student-enrolment-statuses');
    }

    public function view(User $user, StudentEnrolmentStatus $studentEnrolmentStatus): bool
    {
        return $user->can('viewAny:student-enrolment-statuses') || $user->can('view:student-enrolment-statuses');
    }

    public function create(User $user): bool
    {
        return $user->can('create:student-enrolment-statuses');
    }

    public function update(User $user, StudentEnrolmentStatus $studentEnrolmentStatus): bool
    {
        return $user->can('update:student-enrolment-statuses');
    }

    public function delete(User $user, StudentEnrolmentStatus $studentEnrolmentStatus): bool
    {
        return $user->can('delete:student-enrolment-statuses');
    }

    public function restore(User $user, StudentEnrolmentStatus $studentEnrolmentStatus): bool
    {
        return $user->can('restore:student-enrolment-statuses');
    }

    public function forceDelete(User $user, StudentEnrolmentStatus $studentEnrolmentStatus): bool
    {
        return $user->can('forceDelete:student-enrolment-statuses');
    }
}
