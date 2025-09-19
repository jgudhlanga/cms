<?php

namespace App\Helpers;

use App\Models\Institution\DepartmentApplicationStep;
use Illuminate\Database\Eloquent\Collection;

class WorkflowHelper
{
    /**
     * Get a step by its position.
    */
    public static function getDepartmentApplicationStepByPosition(int $departmentId, int $position): ?DepartmentApplicationStep
    {
        return DepartmentApplicationStep::where('institution_department_id', $departmentId)->where('position', $position)->first();
    }

    public static function getAllPendingSteps(int $departmentId, int $currentPosition): Collection
    {
        return DepartmentApplicationStep::where('institution_department_id', $departmentId)->where('position' , '>', $currentPosition)->get();
    }

     public static function getAllSteps(int $departmentId): Collection
    {
        return DepartmentApplicationStep::where('institution_department_id', $departmentId)->get();
    }

    public static function getMaxStep(int $departmentId): ?DepartmentApplicationStep
    {
        return DepartmentApplicationStep::where('institution_department_id', $departmentId)->orderByDesc('position')->first();
    }
}
