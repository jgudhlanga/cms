<?php

namespace App\Repositories\Shared;


use App\DTO\Shared\WorkflowStepDto;
use App\Http\Filters\Shared\SharedNameFilter;
use App\Models\Shared\WorkflowStep;
use App\Repositories\Base\BaseRepository;
use App\Repositories\Shared\interface\IWorkflowStepRepository;

class WorkflowStepRepository extends BaseRepository implements IWorkflowStepRepository
{
    public function __construct(protected WorkflowStep $workflowStep)
    {
        parent::__construct($this->workflowStep);
    }

    public function create(WorkflowStepDto $dto): WorkflowStep
    {
        return $this->workflowStep->create($this->getFields($dto))->refresh();
    }

    public function update(WorkflowStep $workflowStep, WorkflowStepDto $dto): WorkflowStep
    {
        return tap($workflowStep)->update($this->getFields($dto));
    }

    public function allFilter($columns = ['*'], SharedNameFilter $filters = null)
    {
        return $this->workflowStep
            ->select($columns)
            ->filter($filters)
            ->orderBy('position', 'asc')
            ->orderBy('name')
            ->orderBy('deleted_at')
            ->paginate()
            ->withQueryString();
    }

    private function getFields(WorkflowStepDto $dto): array
    {
        return [
            'name' => $dto->name,
            'description' => $dto->description,
        ];
    }
}
