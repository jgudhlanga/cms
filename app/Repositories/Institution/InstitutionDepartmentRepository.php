<?php

namespace App\Repositories\Institution;

use App\DTO\Institution\InstitutionDepartmentDto;
use App\Http\Filters\Institution\DepartmentFilter;
use App\Models\Institution\InstitutionDepartment;
use App\Repositories\Base\BaseRepository;
use App\Repositories\Institution\interface\IInstitutionDepartmentRepository;

class InstitutionDepartmentRepository extends BaseRepository implements IInstitutionDepartmentRepository
{
    public function __construct(protected InstitutionDepartment $institutionDepartment)
    {
        parent::__construct($this->institutionDepartment);
    }

    public function create(InstitutionDepartmentDto $dto): InstitutionDepartment
    {
        return $this->institutionDepartment->create($this->getFields($dto))->refresh();
    }

    public function update(InstitutionDepartment $institutionDepartment, InstitutionDepartmentDto $dto): InstitutionDepartment
    {
        return tap($institutionDepartment)->update($this->getFields($dto));
    }

    public function allFilter($columns = ['*'], DepartmentFilter $filters = null)
    {
        return $this->institutionDepartment
            ->select($columns)
            ->filter($filters)
            ->orderBy('deleted_at')
            ->paginate()
            ->withQueryString();
    }

    private function getFields(InstitutionDepartmentDto $dto): array
    {
        return [
            'department_id' => $dto->department_id,
            'description' => $dto->description,
        ];
    }
}
