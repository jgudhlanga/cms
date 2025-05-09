<?php

namespace App\Repositories\Institution;

use App\DTO\Institution\DepartmentLevelDto;
use App\Models\Institution\DepartmentLevel;
use App\Models\Institution\InstitutionDepartment;
use App\Repositories\Base\BaseRepository;
use App\Repositories\Institution\interface\IDepartmentLevelRepository;

class DepartmentLevelRepository extends BaseRepository implements IDepartmentLevelRepository
{
    public function __construct(protected DepartmentLevel $departmentLevel)
    {
        parent::__construct($this->departmentLevel);
    }


    public function syncDepartmentLevels(InstitutionDepartment $institutionDepartment, DepartmentLevelDto $dto): void
    {
        // Get existing level_ids linked to this department
        $existing = $this->departmentLevel
            ->where('institution_department_id', $institutionDepartment->id)
            ->pluck('level_id')
            ->toArray();

        $newIds = $dto->level_ids;

        // Determine which IDs to add and which to remove
        $toAdd = array_diff($newIds, $existing);
        $toRemove = array_diff($existing, $newIds);

        // Delete removed levels
        if (!empty($toRemove)) {
            $this->departmentLevel->whereIn('level_id', $toRemove)->delete();
        }

        // Add new levels
        foreach ($toAdd as $levelId) {
            $this->departmentLevel->create(['institution_department_id' => $institutionDepartment->id, 'level_id' => $levelId]);
        }
    }
}
