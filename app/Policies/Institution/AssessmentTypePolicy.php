<?php

namespace App\Policies\Institution;

use App\Models\Institution\AssessmentType;
use App\Models\Users\User;

class AssessmentTypePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('viewAny:assessment-types');
    }

    public function view(User $user, AssessmentType $assessmentType): bool
    {
        return $user->can('viewAny:assessment-types') || $user->can('view:assessment-types');
    }

    public function create(User $user): bool
    {
        return $user->can('create:assessment-types');
    }

    public function update(User $user, AssessmentType $assessmentType): bool
    {
        return $user->can('update:assessment-types');
    }

    public function delete(User $user, AssessmentType $assessmentType): bool
    {
        return $user->can('delete:assessment-types');
    }

    public function restore(User $user, AssessmentType $assessmentType): bool
    {
        return $user->can('restore:assessment-types');
    }

    public function forceDelete(User $user, AssessmentType $assessmentType): bool
    {
        return $user->can('forceDelete:assessment-types');
    }
}
