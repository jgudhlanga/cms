<?php

namespace App\Policies\Lecturer;

use App\Models\Users\User;

class LecturerPortalPolicy
{
    public function viewLecturerDashboard(User $user): bool
    {
        return $user->can('view:lecturer-dashboard');
    }

    public function viewLecturerClasses(User $user): bool
    {
        return $user->can('view:lecturer-classes');
    }

    public function viewLecturerModules(User $user): bool
    {
        return $user->can('view:lecturer-modules');
    }
}
