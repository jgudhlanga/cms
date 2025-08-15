<?php

namespace App\Http\Controllers\Api\V1\Institution;

use App\Http\Controllers\Controller;
use App\Http\Resources\Institution\DepartmentLevelRequirementResource;
use App\Http\Resources\Institution\DepartmentLevelResource;
use App\Models\Institution\DepartmentLevel;
use App\Models\Institution\InstitutionDepartment;
use Illuminate\Http\Request;

class DepartmentLevelController extends Controller
{
    public function index(InstitutionDepartment $institutionDepartment)
    {

        return DepartmentLevelResource::collection($institutionDepartment->departmentLevels);
    }

    public function levelRequirements(DepartmentLevel $departmentLevel)
    {
        return $departmentLevel->requirement ? DepartmentLevelRequirementResource::make($departmentLevel->requirement) : null;
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
