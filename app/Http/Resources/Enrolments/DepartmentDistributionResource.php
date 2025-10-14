<?php

namespace App\Http\Resources\Enrolments;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DepartmentDistributionResource extends JsonResource
{

    public function toArray(Request $request): array
    {
        return [
            'departmentId' => $this->department_id,
            'departmentName' => $this->department_name,
            'applicationCount' => $this->application_count,
            'maleCount' => $this->male_count,
            'femaleCount' => $this->female_count,
            'disabledCount' => $this->disabled_count,
        ];
    }
}
