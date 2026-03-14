<?php

namespace App\Http\Resources\Students;

use App\Http\Resources\Users\UserSummaryResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class StudentResource extends JsonResource
{

    public function toArray(Request $request): array
    {
        return [
            'type' => 'student',
            'id' => $this?->id ?? null,
            'attributes' => [
                'userId' => $this?->user_id ?? null,
                'titleId' => $this?->title_id ?? null,
                'title' => $this->title?->name ?? null,
                'genderId' => $this?->gender_id ?? null,
                'gender' => $this->gender?->title ?? null,
                'maritalStatusId' => $this?->marital_status_id ?? null,
                'maritalStatus' => $this->maritalStatus?->title ?? null,
                'raceId' => $this?->race_id ?? null,
                'race' => $this->race?->title ?? null,
                'idTypeId' => $this?->id_type_id ?? null,
                'idType' => $this?->idType?->name ?? null,
                'studentNumber' => $this?->student_number ?? null,
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
                'requiredExamSittingCount' => $this?->required_exam_sitting_count ?? null,
                'disabilityStatus' => $this?->disability_status,
                'createdAt' => $this->created_at,
                'updatedAt' => $this->updated_at,
                'deletedAt' => $this->deleted_at,
            ],
            'relationships' => [
                'user' => UserSummaryResource::make($this->user),
            ]

        ];
    }
}
