<?php

namespace App\Repositories\Institution;

use App\DTO\Institution\DepartmentDto;
use App\Http\Filters\Shared\SharedNameFilter;
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
        return $this->department->create([
            'name' => $dto->name,
            'description' => $dto->description,
        ])->refresh();
    }

    public function update(Department $department, DepartmentDto $dto): Department
    {
        return tap($department)->update([
            'name' => $dto->name,
            'description' => $dto->description,
        ]);
    }

    public function allFilter($columns = ['*'], SharedNameFilter $filters = null)
    {
        return $this->department
            ->select($columns)
            ->filter($filters)
            ->orderBy('name')
            ->orderBy('deleted_at')
            ->paginate()
            ->withQueryString();
    }
}
