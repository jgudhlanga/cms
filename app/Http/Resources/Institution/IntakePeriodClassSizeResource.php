<?php

namespace App\Http\Resources\Institution;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class IntakePeriodClassSizeResource extends JsonResource
{

    public function toArray(Request $request): array
    {
        return [
            'type' => 'IntakePeriodClassSize',
            'id' => $this->id,
            'attributes' => [
                'institutionDepartmentId' => $this->institution_department_id,
                'departmentCourseId' => $this->department_course_id,
                'departmentLevelId' => $this->department_level_id,
                'classSize' => $this->class_size,
                'intakePeriodId' => $this->intake_period_id,
            ],
        ];
    }
}
