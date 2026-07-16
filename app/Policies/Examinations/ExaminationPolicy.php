<?php

namespace App\Policies\Examinations;

use App\Models\Examinations\ExaminationResult;
use App\Models\Users\User;

class ExaminationPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('viewAny:examinations');
    }

    public function view(User $user, ?ExaminationResult $examinationResult = null): bool
    {
        return $user->can('viewAny:examinations') || $user->can('view:examinations');
    }

    public function create(User $user): bool
    {
        return $user->can('create:examinations');
    }

    public function update(User $user, ?ExaminationResult $examinationResult = null): bool
    {
        return $user->can('update:examinations');
    }

    public function delete(User $user, ?ExaminationResult $examinationResult = null): bool
    {
        return $user->can('delete:examinations');
    }

    public function restore(User $user, ?ExaminationResult $examinationResult = null): bool
    {
        return $user->can('restore:examinations');
    }

    public function forceDelete(User $user, ?ExaminationResult $examinationResult = null): bool
    {
        return $user->can('forceDelete:examinations');
    }

    public function viewAuditTrail(User $user): bool
    {
        return $user->can('viewAuditTrail:examinations');
    }

    public function export(User $user): bool
    {
        return $user->can('export:examinations');
    }

    public function import(User $user): bool
    {
        return $user->can('import:examinations');
    }
}
