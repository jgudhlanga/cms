<?php

namespace App\Http\Controllers\Api\V1\Institution;

use App\Http\Controllers\Controller;
use App\Http\Resources\Institution\DepartmentLevelCourseResource;
use App\Models\Applications\ApplicationOfferingCourse;
use App\Models\Institution\DepartmentLevel;
use App\Models\Institution\InstitutionDepartment;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class DepartmentLevelCourseController extends Controller
{
    public function index(DepartmentLevel $departmentLevel): AnonymousResourceCollection
    {
        $courseIds = ApplicationOfferingCourse::query()
            ->whereHas('offeringLevel', fn ($q) => $q->where('department_level_id', $departmentLevel->id))
            ->pluck('department_course_id');

        $courses = $departmentLevel->courses()
            ->join('department_courses', 'department_courses.id', '=', 'department_level_courses.department_course_id')
            ->whereIn('department_courses.id', $courseIds)
            ->get();

        return DepartmentLevelCourseResource::collection($courses);
    }

    public function institutionDepartmentLevelCourses(InstitutionDepartment $institutionDepartment): AnonymousResourceCollection
    {
        $courses = $institutionDepartment
            ->departmentCourses()
            ->with([
                'departmentCourseLevels.departmentLevel.level',
                'departmentCourseLevels.departmentCourse.course',
            ])
            ->get()
            ->flatMap(fn ($departmentCourse) => $departmentCourse->departmentCourseLevels)
            ->unique('id')
            ->values();

        return DepartmentLevelCourseResource::collection($courses);
    }

    public function store(Request $request) {}

    public function show(string $id) {}

    public function update(Request $request, string $id) {}

    public function destroy(string $id) {}
}
