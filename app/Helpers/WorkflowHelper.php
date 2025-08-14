<?php

namespace App\Helpers;

use App\Models\Institution\DepartmentApplicationStep;

class WorkflowHelper
{
    /**
     * Get a step by its position.
     */
    public static function getDepartmentApplicationStepByPosition(int $position): ?DepartmentApplicationStep
    {
        return DepartmentApplicationStep::where('position', $position)->first();
    }

   public static function getMaxStep(): ?DepartmentApplicationStep
{
    return DepartmentApplicationStep::orderByDesc('position')->first();
}
}
