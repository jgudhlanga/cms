<?php

namespace App\Repositories\Institution;

use App\DTO\Institution\DepartmentDto;
use App\Http\Filters\Institution\DepartmentFilter;
use App\Models\Institution\Department;
use App\Repositories\Base\BaseRepository;
use App\Repositories\Institution\interface\IDepartmentRepository;

class DepartmentRepository extends BaseRepository implements IDepartmentRepository
{
    public function __construct(protected Department $department)
    {
        parent::__construct($this->department);
    }

    public function create(DepartmentDto $dto): Department
    {
        return $this->department->create($this->getFields($dto))->refresh();
    }

    public function update(Department $department, DepartmentDto $dto): Department
    {
        return tap($department)->update($this->getFields($dto));
    }

    public function allFilter($columns = ['*'], DepartmentFilter $filters = null)
    {
        return $this->department
            ->select($columns)
            ->filter($filters)
            ->orderBy('is_academic', 'DESC')
            ->orderBy('position')
            ->orderBy('name')
            ->orderBy('deleted_at')
            ->paginate()
            ->withQueryString();
    }

    private function getFields(DepartmentDto $dto): array
    {
        return [
            'name' => $dto->name,
            'is_academic' => $dto->is_academic,
            'description' => $dto->description,
        ];
    }
}
