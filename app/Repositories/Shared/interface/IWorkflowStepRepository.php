<?php

namespace App\Repositories\Shared\interface;

use App\DTO\Shared\WorkflowStepDto;
use App\Http\Filters\Shared\SharedNameFilter;
use App\Models\Shared\WorkflowStep;
use App\Repositories\Base\Interface\IBaseRepository;

interface IWorkflowStepRepository extends IBaseRepository
{
    public function create(WorkflowStepDto $dto);

    public function update(WorkflowStep $workflowStep, WorkflowStepDto $dto);

    public function allFilter($columns = ['*'], SharedNameFilter $filters = null);
}
