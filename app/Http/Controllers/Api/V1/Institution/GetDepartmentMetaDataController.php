<?php

namespace App\Http\Controllers\Api\V1\Institution;

use App\Http\Controllers\Controller;
use App\Http\Resources\Institution\DepartmentLevelResource;
use App\Models\Institution\InstitutionDepartment;
use App\Repositories\Institution\interface\IDepartmentLevelRepository;

class GetDepartmentMetaDataController extends Controller
{

    public function __construct(){

    }
    public function __invoke(InstitutionDepartment $institutionDepartment)
    {

        sleep(10);
        $levels = DepartmentLevelResource::collection($institutionDepartment->departmentLevels);
        return response()->json(compact('levels'));
    }
}
