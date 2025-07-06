<?php

namespace App\Repositories\Institution;

use App\DTO\Institution\DepartmentApplicationStepDto;
use App\DTO\Institution\DepartmentApplicationStepUpdateDto;
use App\Models\Institution\DepartmentApplicationStep;
use App\Models\Institution\InstitutionDepartment;
use App\Repositories\Base\BaseRepository;
use App\Repositories\Institution\interface\IDepartmentApplicationStepRepository;

class DepartmentApplicationStepRepository extends BaseRepository implements IDepartmentApplicationStepRepository
{
    public function __construct(protected DepartmentApplicationStep $departmentApplicationStep)
    {
        parent::__construct($this->departmentApplicationStep);
    }


    public function syncDepartmentApplicationSteps(InstitutionDepartment $institutionDepartment, DepartmentApplicationStepDto $dto): void
    {
        // Get existing application_step_ids linked to this department
        $existing = $this->departmentApplicationStep
            ->where('institution_department_id', $institutionDepartment->id)
            ->pluck('application_step_id')
            ->toArray();

        $newIds = $dto->application_step_ids;

        // Determine which IDs to add and which to remove
        $toAdd = array_diff($newIds, $existing);
        $toRemove = array_diff($existing, $newIds);

        // Delete removed application_steps
        if (!empty($toRemove)) {
            $this->departmentApplicationStep->whereIn('application_step_id', $toRemove)->delete();
        }

        // Add new application_steps
        foreach ($toAdd as $applicationStepId) {
            $this->departmentApplicationStep->create(['institution_department_id' => $institutionDepartment->id, 'application_step_id' => $applicationStepId]);
        }
    }

    public function update(DepartmentApplicationStep $departmentApplicationStep, DepartmentApplicationStepUpdateDto $dto)
    {
        $departmentApplicationStep = tap($departmentApplicationStep)->update([
            'show_on_current_application_period' => $dto->show_on_current_application_period,
        ]);
        # Get existing department_ linked to this department
        $existing = $departmentApplicationStep
            ->departmentApplicationStepLevels()
            ->where('department_course_id', $departmentApplicationStep->id)
            ->pluck('department_level_id')
            ->toArray();

        $newIds = $dto->department_level_ids;

        // Determine which IDs to add and which to remove
        $toAdd = array_diff($newIds, $existing);
        $toRemove = array_diff($existing, $newIds);

        // Delete removed courses
        if (!empty($toRemove)) {
            $departmentApplicationStep->departmentApplicationStepLevels()->whereIn('department_level_id', $toRemove)->delete();
        }

        // Add new courses
        foreach ($toAdd as $departmentLevelId) {
            $departmentApplicationStep->departmentApplicationStepLevels()->create(['department_course_id' => $departmentApplicationStep->id, 'department_level_id' => $departmentLevelId]);
        }
        return $departmentApplicationStep;
    }
}
