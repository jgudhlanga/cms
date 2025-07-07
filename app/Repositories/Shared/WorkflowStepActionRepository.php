<?php

namespace App\Repositories\Shared;


use App\DTO\Shared\WorkflowStepActionDto;
use App\Http\Filters\Shared\SharedTitleFilter;
use App\Models\Shared\WorkflowStepAction;
use App\Repositories\Base\BaseRepository;
use App\Repositories\Shared\interface\IWorkflowStepActionRepository;

class WorkflowStepActionRepository extends BaseRepository implements IWorkflowStepActionRepository
{
    public function __construct(protected WorkflowStepAction $workflowStepAction)
    {
        parent::__construct($this->workflowStepAction);
    }

    public function create(WorkflowStepActionDto $dto): WorkflowStepAction
    {
        return $this->workflowStepAction->create($this->getFields($dto))->refresh();
    }

    public function update(WorkflowStepAction $workflowStepAction, WorkflowStepActionDto $dto): WorkflowStepAction
    {
        return tap($workflowStepAction)->update($this->getFields($dto));
    }

    public function allFilter($columns = ['*'], SharedTitleFilter $filters = null)
    {
        return $this->workflowStepAction
            ->select($columns)
            ->filter($filters)
            ->orderBy('title')
            ->orderBy('deleted_at')
            ->paginate()
            ->withQueryString();
    }

    private function getFields(WorkflowStepActionDto $dto): array
    {
        return [
            'title' => $dto->title,
        ];
    }
}
