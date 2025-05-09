<?php

namespace App\Repositories\Institution;

use App\DTO\Institution\DepartmentCourseDto;
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
}
