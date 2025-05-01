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


    public function allFilter($columns = ['*'], DepartmentFilter $filters = null)
    {
        return $this->institutionDepartment
            ->select($columns)
            ->filter($filters)
            ->orderBy('deleted_at')
            ->paginate()
            ->withQueryString();
    }

    public function syncInstitutionDepartment(InstitutionDepartmentDto $dto): void
    {
        // Get existing department_ids linked to this institution
        $existing = $this->institutionDepartment->pluck('department_id')->toArray();

        $newIds = $dto->department_ids;

        // Determine which IDs to add and which to remove
        $toAdd = array_diff($newIds, $existing);
        $toRemove = array_diff($existing, $newIds);

        // Delete removed departments
        if (!empty($toRemove)) {
            $this->institutionDepartment->whereIn('department_id', $toRemove)->delete();
        }

        // Add new departments
        foreach ($toAdd as $departmentId) {
            $this->institutionDepartment->create(['department_id' => $departmentId]);
        }
    }
}
