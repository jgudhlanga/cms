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
            'fullTimeCount' => $this->full_time_count,
            'partTimeCount' => $this->part_time_count,
            'blockReleaseCount' => $this->block_release_count,
            'ojetCount' => $this->ojet_count,
            'maleCount' => $this->male_count,
            'femaleCount' => $this->female_count,
            'disabledCount' => $this->disabled_count,
            'provisionalCount' => $this->provisional_count,
            'waitingCount' => $this->waiting_count,
            'verifiedCount' => $this->verified_count,
            'finalCount' => $this->final_count,
            'failedCount' => $this->failed_count
        ];
    }
}
