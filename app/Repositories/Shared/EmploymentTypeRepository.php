<?php

namespace App\Repositories\Shared;


use App\DTO\Shared\EmploymentTypeDto;
use App\Http\Filters\Shared\SharedNameFilter;
use App\Models\Shared\EmploymentType;
use App\Repositories\Base\BaseRepository;
use App\Repositories\Shared\interface\IEmploymentTypeRepository;

class EmploymentTypeRepository extends BaseRepository implements IEmploymentTypeRepository
{
    public function __construct(protected EmploymentType $employmentType)
    {
        parent::__construct($this->employmentType);
    }

    public function create(EmploymentTypeDto $dto): EmploymentType
    {
        return $this->employmentType->create($this->getFields($dto))->refresh();
    }

    public function update(EmploymentType $employmentType, EmploymentTypeDto $dto): EmploymentType
    {
        return tap($employmentType)->update($this->getFields($dto));
    }

    public function allFilter($columns = ['*'], SharedNameFilter $filters = null)
    {
        return $this->employmentType
            ->select($columns)
            ->filter($filters)
            ->orderBy('name')
            ->orderBy('deleted_at')
            ->paginate()
            ->withQueryString();
    }

    private function getFields(EmploymentTypeDto $dto): array
    {
        return [
            'name' => $dto->name,
            'description' => $dto->description,
        ];
    }
}
