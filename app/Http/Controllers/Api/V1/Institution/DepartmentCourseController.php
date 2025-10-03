<?php

namespace App\Http\Controllers\Api\V1\Institution;

use App\Http\Controllers\Controller;
use App\Http\Resources\Institution\CourseRequirementResource;
use App\Models\Institution\CourseRequirement;
use App\Models\Institution\DepartmentCourse;
use App\Models\Institution\DepartmentLevel;
use Illuminate\Http\Request;

class DepartmentCourseController extends Controller
{

    public function courseRequirements(DepartmentLevel $departmentLevel, DepartmentCourse $departmentCourse)
    {
        $requirement = CourseRequirement::where('department_course_id', $departmentCourse->id)
            ->where('department_level_id', $departmentLevel->id)
            ->first();

        return $requirement ? CourseRequirementResource::make($requirement) : null;
    }

    public function store(Request $request)
    {
    }

    public function show(string $id)
    {
    }

    public function update(Request $request, string $id)
    {
    }

    public function destroy(string $id)
    {
    }
}
