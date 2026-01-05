<?php

namespace App\Http\Controllers\Api\V1\Institution;

use App\Http\Controllers\Controller;
use App\Http\Resources\Institution\DepartmentLevelCourseResource;
use App\Http\Resources\Institution\DepartmentLevelResource;
use App\Models\Institution\DepartmentLevel;
use App\Models\Institution\DepartmentLevelCourse;
use App\Models\Institution\InstitutionDepartment;
use Illuminate\Http\Request;

class DepartmentLevelCourseController extends Controller
{
    public function index(DepartmentLevel $departmentLevel)
    {
        dd($departmentLevel->courses);
        $courses = $departmentLevel->courses()->join('department_level_courses', 'department_level_courses.course_id', '=', 'courses.id');
        /*$courses = DepartmentLevelCourse::where('department_level_id', $departmentLevel->id)
            ->where('show_on_current_application_period', true)
            ->select('*')
            ->orderBy('level_id', 'asc')
            ->orderBy('created_at')
            ->orderBy('deleted_at')
            ->get();*/
        //return DepartmentLevelCourseResource::collection($departmentLevel->courses);
        return DepartmentLevelCourseResource::collection($departmentLevel->courses);
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
