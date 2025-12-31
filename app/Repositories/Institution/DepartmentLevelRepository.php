<?php

namespace App\Repositories\Institution;

use Illuminate\Support\Facades\DB;
use App\DTO\Institution\DepartmentLevelDto;
use App\DTO\Institution\DepartmentLevelRequirementsDto;
use App\Models\Institution\DepartmentLevel;
use App\Models\Institution\InstitutionDepartment;
use App\Repositories\Base\BaseRepository;
use App\Repositories\Institution\interface\IDepartmentLevelRepository;
use Throwable;

class DepartmentLevelRepository extends BaseRepository implements IDepartmentLevelRepository
{
    public function __construct(protected DepartmentLevel $departmentLevel)
    {
        parent::__construct($this->departmentLevel);
    }

    /**
     * @throws Throwable
     */
    public function syncDepartmentLevels(
        InstitutionDepartment $institutionDepartment,
        DepartmentLevelDto    $dto
    ): void
    {
        DB::transaction(function () use ($institutionDepartment, $dto) {

            $newIds = $dto->level_ids;
            $showOnCurrent = $dto->show_on_current_application_period ?? [];
            // Existing level IDs for this department
            $existing = $this->departmentLevel
                ->where('institution_department_id', $institutionDepartment->id)
                ->pluck('level_id')
                ->toArray();

            $toAdd = array_diff($newIds, $existing);
            $toRemove = array_diff($existing, $newIds);
            $toUpdate = array_intersect($existing, $newIds);
            // Remove unlinked levels (scoped!)
            if (!empty($toRemove)) {
                $this->departmentLevel
                    ->where('institution_department_id', $institutionDepartment->id)
                    ->whereIn('level_id', $toRemove)
                    ->delete();
            }
            // Add new levels
            foreach ($toAdd as $levelId) {
                $this->departmentLevel->create([
                    'institution_department_id' => $institutionDepartment->id,
                    'level_id' => $levelId,
                    'show_on_current_application_period' =>
                        in_array($levelId, $showOnCurrent, true),
                ]);
            }
            // Update existing levels
            foreach ($toUpdate as $levelId) {
                $this->departmentLevel
                    ->where('institution_department_id', $institutionDepartment->id)
                    ->where('level_id', $levelId)
                    ->update([
                        'show_on_current_application_period' =>
                            in_array($levelId, $showOnCurrent, true),
                    ]);
            }
        });
    }


    public function updateDepartmentLevelRequirements(DepartmentLevel $departmentLevel, DepartmentLevelRequirementsDto $dto): void
    {
        if (!empty($departmentLevel->requirement)) {
            $departmentLevel->requirement()->update($this->getFields($dto));
        } else {
            $departmentLevel->requirement()->create(array_merge(['department_level_id' => $departmentLevel->id], $this->getFields($dto)));
        }
    }

    private function getFields(DepartmentLevelRequirementsDto $dto): array
    {
        return [
            'is_o_level_required' => $dto->is_o_level_required,
            'required_subjects_count' => $dto->required_subjects_count,
            'main_subjects_count' => $dto->main_subjects_count,
            'main_subject_ids' => $dto->main_subject_ids, // Array
            'other_subjects_count' => $dto->other_subjects_count,
            'only_read_write_required' => $dto->only_read_write_required,
            'required_level_id' => $dto->required_level_id,
        ];
    }
}
