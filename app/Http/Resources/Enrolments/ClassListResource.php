<?php

namespace App\Http\Resources\Enrolments;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ClassListResource extends JsonResource
{

    public function toArray(Request $request): array
    {
        return [
            'studentApplicationId' => $this->student_application_id,
            'attributes' => ClassListAttributesResource::make($this->attributes),
            'type' => $this->type,
        ];
    }
}
