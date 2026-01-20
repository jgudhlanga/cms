<?php

namespace App\Repositories\Institution;

use App\DTO\Institution\DepartmentCourseDto;
use App\DTO\Institution\DepartmentCourseUpdateDto;
use App\DTO\Institution\CourseRequirementsDto;
use App\Models\Institution\DepartmentCourse;
use App\Models\Institution\InstitutionDepartment;
use App\Repositories\Base\BaseRepository;
use App\Repositories\Institution\interface\IDepartmentCourseRepository;

class DepartmentCourseRepository extends BaseRepository implements IDepartmentCourseRepository
{
    public function __construct(protected DepartmentCourse $departmentCourse)
    {
        parent::__construct($this->departmentCourse);
    }


    public function syncDepartmentCourses(InstitutionDepartment $institutionDepartment, DepartmentCourseDto $dto): void
    {
        // Get existing course_ids linked to this department
        $existing = $this->departmentCourse
            ->where('institution_department_id', $institutionDepartment->id)
            ->pluck('course_id')
            ->toArray();

        $newIds = $dto->course_ids;

        // Determine which IDs to add and which to remove
        $toAdd = array_diff($newIds, $existing);
        $toRemove = array_diff($existing, $newIds);

        // Delete removed courses
        if (!empty($toRemove)) {
            $this->departmentCourse->whereIn('course_id', $toRemove)->delete();
        }

        // Add new courses
        foreach ($toAdd as $courseId) {
            $this->departmentCourse->create(['institution_department_id' => $institutionDepartment->id, 'course_id' => $courseId]);
        }
    }

    public function update(DepartmentCourse $departmentCourse, DepartmentCourseUpdateDto $dto)
    {
        $departmentCourse = tap($departmentCourse)->update(['show_on_current_application_period' => $dto->show_on_current_application_period]);
        # Get existing department_ linked to this department
        $existingCourseLevels = $departmentCourse->departmentCourseLevels()->where('department_course_id', $departmentCourse->id)->pluck('department_level_id')->toArray();
        $newCourseLevelIds = $dto->department_level_ids;

        // Determine which IDs to add and which to remove
        $toAddCourseLevels = array_diff($newCourseLevelIds, $existingCourseLevels);
        $toRemoveCourseLevels = array_diff($existingCourseLevels, $newCourseLevelIds);

        // Delete removed courses
        if (!empty($toRemoveCourseLevels)) {
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
        if (!empty($departmentCourse->requirement)) {
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
