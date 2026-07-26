<?php

namespace App\Policies\Shared;

use App\Models\Shared\WorkflowStep;
use App\Models\Users\User;

class WorkflowStepPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('viewAny:workflow-steps');
    }

    public function view(User $user, WorkflowStep $workflowStep): bool
    {
        return $user->can('viewAny:workflow-steps') || $user->can('view:workflow-steps');
    }

    public function create(User $user): bool
    {
        return $user->can('create:workflow-steps');
    }

    public function update(User $user, WorkflowStep $workflowStep): bool
    {
        return $user->can('update:workflow-steps');
    }

    public function delete(User $user, WorkflowStep $workflowStep): bool
    {
        return $user->can('delete:workflow-steps');
    }

    public function restore(User $user, WorkflowStep $workflowStep): bool
    {
        return $user->can('restore:workflow-steps');
    }

    public function forceDelete(User $user, WorkflowStep $workflowStep): bool
    {
        return $user->can('forceDelete:workflow-steps');
    }
}
