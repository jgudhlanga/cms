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
            'titleId' => $this?->title_id ?? null,
            'title' => $this->title?->name ?? null,
            'genderId' => $this?->gender_id ?? null,
            'gender' => $this->gender?->title ?? null,
            'maritalStatusId' => $this?->marital_status_id ?? null,
            'maritalStatus' => $this->maritalStatus?->title ?? null,
            'raceId' => $this?->race_id ?? null,
            'race' => $this->race?->title ?? null,
            'idType' => $this?->id_type ?? null,
            'idNumber' => $this?->id_number ?? null,
            'passportNumber' => $this?->passport_number ?? null,
            'countryId' => $this?->country_id ?? null,
            'country' => $this->country?->name ?? null,
            'studyPermitNumber' => $this?->study_permit_number ?? null,
            'dateOfBirth' => $this?->date_of_birth ?? null,
            'religionId' => $this?->religion_id ?? null,
            'religion' => $this->religion?->name ?? null,
            'denomination' => $this?->denomination ?? null,
            'height' => $this?->height ?? null,
            'weight' => $this?->weight ?? null,
        ];
    }
}
