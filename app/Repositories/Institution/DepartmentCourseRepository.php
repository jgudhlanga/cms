<?php

namespace App\Repositories\Institution;

use App\DTO\Institution\CourseRequirementsDto;
use App\DTO\Institution\DepartmentCourseDto;
use App\DTO\Institution\DepartmentCourseUpdateDto;
use App\Models\Institution\DepartmentCourse;
use App\Models\Institution\InstitutionDepartment;
use App\Repositories\Base\BaseRepository;
use App\Repositories\Institution\interface\IDepartmentCourseRepository;
use App\Services\Institution\ProgrammeLinkUsageGuard;
use Illuminate\Support\Facades\DB;

class DepartmentCourseRepository extends BaseRepository implements IDepartmentCourseRepository
{
    public function __construct(
        protected DepartmentCourse $departmentCourse,
        protected ProgrammeLinkUsageGuard $usageGuard,
    ) {
        parent::__construct($this->departmentCourse);
    }

    public function syncDepartmentCourses(InstitutionDepartment $institutionDepartment, DepartmentCourseDto $dto): void
    {
        DB::transaction(function () use ($institutionDepartment, $dto) {
            $newIds = array_values(array_unique(array_filter(
                array_map('intval', $dto->course_ids),
                fn (int $courseId): bool => $courseId > 0,
            )));

            // Trashed links are kept in view so a course that is checked again is
            // restored on its original id instead of orphaning existing records.
            $links = $this->departmentCourse
                ->withTrashed()
                ->where('institution_department_id', $institutionDepartment->id)
                ->get();

            $activeCourseIds = $links->whereNull('deleted_at')->pluck('course_id')->map('intval')->all();
            $toRemove = array_values(array_diff($activeCourseIds, $newIds));

            if ($toRemove !== []) {
                $removable = $links->whereNull('deleted_at')
                    ->whereIn('course_id', $toRemove);

                $this->usageGuard->assertCoursesUnused($removable->pluck('id')->map('intval')->all());

                $this->departmentCourse
                    ->whereIn('id', $removable->pluck('id')->all())
                    ->delete();
            }

            foreach ($newIds as $courseId) {
                $existing = $links->firstWhere('course_id', $courseId);

                if ($existing === null) {
                    $this->departmentCourse->create([
                        'institution_department_id' => $institutionDepartment->id,
                        'course_id' => $courseId,
                    ]);

                    continue;
                }

                if ($existing->trashed()) {
                    $existing->restore();
                }
            }
        });
    }

    public function update(DepartmentCourse $departmentCourse, DepartmentCourseUpdateDto $dto)
    {
        $attributes = [];

        if ($dto->coursework_capture_enabled !== null) {
            $attributes['coursework_capture_enabled'] = $dto->coursework_capture_enabled;
        }

        if ($attributes !== []) {
            $departmentCourse = tap($departmentCourse)->update($attributes);
        } else {
            $departmentCourse = $departmentCourse->fresh() ?? $departmentCourse;
        }
        // Get existing department_ linked to this department
        $existingCourseLevels = $departmentCourse->departmentCourseLevels()->where('department_course_id', $departmentCourse->id)->pluck('department_level_id')->toArray();
        $newCourseLevelIds = $dto->department_level_ids;

        // Determine which IDs to add and which to remove
        $toAddCourseLevels = array_diff($newCourseLevelIds, $existingCourseLevels);
        $toRemoveCourseLevels = array_diff($existingCourseLevels, $newCourseLevelIds);

        // Delete removed courses
        if (! empty($toRemoveCourseLevels)) {
            $departmentCourse->departmentCourseLevels()->whereIn('department_level_id', $toRemoveCourseLevels)->delete();
        }
        // Add new courses
        foreach ($toAddCourseLevels as $departmentLevelId) {
            $departmentCourse->departmentCourseLevels()->create(['department_course_id' => $departmentCourse->id, 'department_level_id' => $departmentLevelId]);
        }

        return $departmentCourse;
    }

    public function updateLevelCourseRequirements(DepartmentCourse $departmentCourse, CourseRequirementsDto $dto): void
    {
        if (! empty($departmentCourse->requirement)) {
            $departmentCourse->requirement()->update($this->getFields($dto));
        } else {
            $departmentCourse->requirement()->create(array_merge([
                'department_course_id' => $departmentCourse->id,
            ], $this->getFields($dto)));
        }
    }

    private function getFields(CourseRequirementsDto $dto): array
    {
        return [
            'department_level_id' => $dto->department_level_id,
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
