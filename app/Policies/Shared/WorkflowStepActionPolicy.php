<?php

namespace App\Policies\Shared;

use App\Models\Shared\WorkflowStepAction;
use App\Models\Users\User;

class WorkflowStepActionPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('viewAny:workflow-step-actions');
    }

    public function view(User $user, WorkflowStepAction $workflowStepAction): bool
    {
        return $user->can('viewAny:workflow-step-actions') || $user->can('view:workflow-step-actions');
    }

    public function create(User $user): bool
    {
        return $user->can('create:workflow-step-actions');
    }

    public function update(User $user, WorkflowStepAction $workflowStepAction): bool
    {
        return $user->can('update:workflow-step-actions');
    }

    public function delete(User $user, WorkflowStepAction $workflowStepAction): bool
    {
        return $user->can('delete:workflow-step-actions');
    }

    public function restore(User $user, WorkflowStepAction $workflowStepAction): bool
    {
        return $user->can('restore:workflow-step-actions');
    }

    public function forceDelete(User $user, WorkflowStepAction $workflowStepAction): bool
    {
        return $user->can('forceDelete:workflow-step-actions');
    }
}
