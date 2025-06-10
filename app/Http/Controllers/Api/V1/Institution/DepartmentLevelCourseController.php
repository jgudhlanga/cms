<?php

namespace App\Http\Controllers\Api\V1\Institution;

use App\Http\Controllers\Controller;
use App\Http\Resources\Institution\DepartmentLevelCourseResource;
use App\Http\Resources\Institution\DepartmentLevelResource;
use App\Models\Institution\DepartmentLevel;
use App\Models\Institution\InstitutionDepartment;
use Illuminate\Http\Request;

class DepartmentLevelCourseController extends Controller
{
    public function index(InstitutionDepartment $institutionDepartment, DepartmentLevel $departmentLevel)
    {
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
