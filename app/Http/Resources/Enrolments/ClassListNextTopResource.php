<?php

namespace App\Http\Resources\Enrolments;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ClassListNextTopResource extends JsonResource
{

    public function toArray(Request $request): array
    {
        $nameParts = array_filter([
            $this->first_name,
            $this->middle_name,
            $this->last_name,
        ]);
        return [
            'applicationId' => $this->application_id,
            'name' => implode(' ', $nameParts)
        ];
    }
}
