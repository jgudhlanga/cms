<?php

namespace App\Repositories\Shared\interface;

use App\DTO\Shared\WorkflowStepActionDto;
use App\Http\Filters\Shared\SharedTitleFilter;
use App\Models\Shared\WorkflowStepAction;
use App\Repositories\Base\Interface\IBaseRepository;

interface IWorkflowStepActionRepository extends IBaseRepository
{
    public function create(WorkflowStepActionDto $dto);

    public function update(WorkflowStepAction $workflowStepAction, WorkflowStepActionDto $dto);

    public function allFilter($columns = ['*'], SharedTitleFilter $filters = null);
}
