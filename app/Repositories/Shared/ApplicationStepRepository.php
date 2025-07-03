<?php

namespace App\Repositories\Shared;


use App\DTO\Shared\ApplicationStepDto;
use App\Http\Filters\Shared\SharedNameFilter;
use App\Models\Shared\ApplicationStep;
use App\Repositories\Base\BaseRepository;
use App\Repositories\Shared\interface\IApplicationStepRepository;

class ApplicationStepRepository extends BaseRepository implements IApplicationStepRepository
{
    public function __construct(protected ApplicationStep $applicationStep)
    {
        parent::__construct($this->applicationStep);
    }

    public function create(ApplicationStepDto $dto): ApplicationStep
    {
        return $this->applicationStep->create($this->getFields($dto))->refresh();
    }

    public function update(ApplicationStep $applicationStep, ApplicationStepDto $dto): ApplicationStep
    {
        return tap($applicationStep)->update($this->getFields($dto));
    }

    public function allFilter($columns = ['*'], SharedNameFilter $filters = null)
    {
        return $this->applicationStep
            ->select($columns)
            ->filter($filters)
            ->orderBy('position', 'asc')
            ->orderBy('name')
            ->orderBy('deleted_at')
            ->paginate()
            ->withQueryString();
    }

    private function getFields(ApplicationStepDto $dto): array
    {
        return [
            'name' => $dto->name,
            'description' => $dto->description,
        ];
    }
}
