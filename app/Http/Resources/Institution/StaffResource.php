<?php

namespace App\Http\Resources\Institution;

use App\Http\Resources\Acl\RoleResource;
use App\Http\Resources\Users\UserResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class StaffResource extends JsonResource
{

    public function toArray(Request $request): array
    {
        return [
            'type' => 'staff',
            'id' => $this->id,
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
                'employmentTypeId' => $this?->employment_type_id ?? null,
                'employmentType' => $this?->employmentType?->name ?? null,
                'idType' => $this?->id_type ?? null,
                'idNumber' => $this?->id_number ?? null,
                'passportNumber' => $this?->passport_number ?? null,
                'countryId' => $this?->country_id ?? null,
                'country' => $this->country?->name ?? null,
                'workPermitNumber' => $this?->work_permit_number ?? null,
                'employeeNumber' => $this?->employee_number ?? null,
                'staffIdNumber' => $this?->staff_id_number ?? null,
                'dateOfBirth' => $this?->date_of_birth ?? null,
                'religionId' => $this?->religion_id ?? null,
                'religion' => $this->religion?->name ?? null,
                'denomination' => $this?->denomination ?? null,
                'height' => $this?->height ?? null,
                'weight' => $this?->weight ?? null,
                'createdAt' => $this->created_at,
                'updatedAt' => $this->updated_at,
                'deletedAt' => $this->deleted_at,
            ],
            'relationships' => [
                'user' => UserResource::make($this->user),
                'roles' => RoleResource::collection($this->user->roles),
            ]
        ];
    }
}
