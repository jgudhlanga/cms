<?php

namespace App\Http\Resources\Students;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class StudentResource extends JsonResource
{

    public function toArray(Request $request): array
    {
        return [
            'userId' => $this?->user_id ?? null,
            'titleId' => $this->title_id,
            'title' => $this->title?->name,
            'genderId' => $this->gender_id,
            'gender' => $this->gender?->title,
            'maritalStatusId' => $this->marital_status_id,
            'maritalStatus' => $this->maritalStatus?->title,
            'raceId' => $this->race_id,
            'race' => $this->race?->title,
            'idType' => $this->id_type,
            'idNumber' => $this->id_number,
            'passportNumber' => $this->passport_number,
            'countryId' => $this->country_id,
            'country' => $this->country?->name,
            'studyPermitNumber' => $this->study_permit_number,
            'dateOfBirth' => $this->date_of_birth,
            'religionId' => $this->religion_id,
            'religion' => $this->religion?->name,
            'denomination' => $this->denomination,
            'height' => $this->height,
            'weight' => $this->weight,
        ];
    }
}
