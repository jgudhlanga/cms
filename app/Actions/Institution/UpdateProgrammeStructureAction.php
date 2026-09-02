<?php

declare(strict_types=1);

namespace App\Actions\Institution;

use App\Models\Institution\DepartmentLevelCourse;
use Illuminate\Support\Facades\DB;

class UpdateProgrammeStructureAction
{
    public function __construct(
        protected SyncProgrammeSemestersForOfferingAction $syncProgrammeSemesters,
    ) {}

    /**
     * @param  array{
     *     duration_years: float|int|string,
     *     taught_semester_count: int,
     *     includes_industrial_attachment: bool,
     *     attachment_semester_count: int
     * }  $data
     */
    public function execute(DepartmentLevelCourse $departmentLevelCourse, array $data): DepartmentLevelCourse
    {
        return DB::transaction(function () use ($departmentLevelCourse, $data): DepartmentLevelCourse {
            $departmentLevelCourse->update([
                'duration_years' => round((float) $data['duration_years'], 1),
                'taught_semester_count' => (int) $data['taught_semester_count'],
                'includes_industrial_attachment' => (bool) $data['includes_industrial_attachment'],
                'attachment_semester_count' => (bool) $data['includes_industrial_attachment']
                    ? max(1, (int) $data['attachment_semester_count'])
                    : 0,
            ]);

            $this->syncProgrammeSemesters->execute($departmentLevelCourse->fresh() ?? $departmentLevelCourse);

            return $departmentLevelCourse->fresh(['programmeSemesters', 'departmentLevel.level']) ?? $departmentLevelCourse;
        });
    }
}
