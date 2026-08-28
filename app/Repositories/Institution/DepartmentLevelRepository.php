<?php

namespace App\Repositories\Institution;

use App\DTO\Institution\DepartmentLevelDto;
use App\DTO\Institution\DepartmentLevelRequirementsDto;
use App\Models\Institution\DepartmentLevel;
use App\Models\Institution\InstitutionDepartment;
use App\Repositories\Base\BaseRepository;
use App\Repositories\Institution\interface\IDepartmentLevelRepository;
use App\Services\Institution\ProgrammeLinkUsageGuard;
use Illuminate\Support\Facades\DB;
use Throwable;

class DepartmentLevelRepository extends BaseRepository implements IDepartmentLevelRepository
{
    public function __construct(
        protected DepartmentLevel $departmentLevel,
        protected ProgrammeLinkUsageGuard $usageGuard,
    ) {
        parent::__construct($this->departmentLevel);
    }

    /**
     * @throws Throwable
     */
    public function syncDepartmentLevels(
        InstitutionDepartment $institutionDepartment,
        DepartmentLevelDto $dto
    ): void {
        DB::transaction(function () use ($institutionDepartment, $dto) {
            $newIds = array_values(array_unique(array_filter(
                array_map('intval', $dto->level_ids),
                fn (int $levelId): bool => $levelId > 0,
            )));

            // Trashed links are kept in view so a level that is checked again is
            // restored on its original id instead of orphaning existing records.
            $links = $this->departmentLevel
                ->withTrashed()
                ->where('institution_department_id', $institutionDepartment->id)
                ->get();

            $activeLevelIds = $links->whereNull('deleted_at')->pluck('level_id')->map('intval')->all();
            $toRemove = array_values(array_diff($activeLevelIds, $newIds));

            if ($toRemove !== []) {
                $removable = $links->whereNull('deleted_at')
                    ->whereIn('level_id', $toRemove);

                $this->usageGuard->assertLevelsUnused($removable->pluck('id')->map('intval')->all());

                $this->departmentLevel
                    ->whereIn('id', $removable->pluck('id')->all())
                    ->delete();
            }

            foreach ($newIds as $levelId) {
                $existing = $links->firstWhere('level_id', $levelId);

                if ($existing === null) {
                    $this->departmentLevel->create([
                        'institution_department_id' => $institutionDepartment->id,
                        'level_id' => $levelId,
                    ]);

                    continue;
                }

                if ($existing->trashed()) {
                    $existing->restore();
                }
            }
        });
    }

    public function updateDepartmentLevelRequirements(DepartmentLevel $departmentLevel, DepartmentLevelRequirementsDto $dto): void
    {
        if (! empty($departmentLevel->requirement)) {
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
            'main_subject_ids' => $dto->main_subject_ids,
            'other_subjects_count' => $dto->other_subjects_count,
            'only_read_write_required' => $dto->only_read_write_required,
            'required_level_id' => $dto->required_level_id,
        ];
    }
}
