<?php

namespace App\Http\Resources\Users;

use App\Helpers\Helper;
use App\Http\Resources\Acl\RoleResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $hasAccessToNonAcademicDepartments = Helper::hasAccessToNonAcademicDepartments();
        return [
            'type' => 'user',
            'id' => $this->id,
            'attributes' => [
                'name' => $this->full_name,
                'firstname' => $this->first_name,
                'middleName' => $this->middle_name,
                'lastname' => $this->last_name,
                'email' => $this->email,
                'phoneNumber' => $this->phone_number,
                'tenantId' => $this->tenant_id,
                'tenant' => $this->tenant?->name,
                'statusId' => $this->status_id,
                'status' => $this->status?->title,
                'lastLoginAt' => $this->last_login_at,
                'loginCount' => $this->login_count ?? 0,
                "avatarUrl" => $this?->avatarUrl,
                "hasStudentProfile" => $this->has_student_profile,
                "studentId" => $this->studentProfile?->id,
                "hasProgram" => $this->studentProfile?->has_program,
                "idNumber" => $this->studentProfile?->id_number,
                "hasStaffProfile" => $this->has_staff_profile,
                "staffId" => $this->staffProfile?->id,
                'canImpersonate' => $this->can_impersonate,
                'canBeImpersonated' => $this->can_be_impersonated,
                "hasAccessToNonAcademicDepartments" => $hasAccessToNonAcademicDepartments,
                $this->mergeWhen($request->routeIs('users.*'), [
                    'createdAt' => $this->created_at,
                    'updatedAt' => $this->updated_at,
                    'deletedAt' => $this->deleted_at,
                ]),
            ],
            'relationships' => [
                'profile' => $this->getProfile(),
                'roles' => RoleResource::collection($this->roles),
            ]
        ];
    }

    private function getProfile(): array
    {
        return [
            'title' => $this->getTitle()['title'],
            'titleId' => $this->getTitle()['titleId'],
            'gender' => $this->getGender()['gender'],
            'genderId' => $this->getGender()['genderId'],
            'maritalStatus' => $this->getMaritalStatus()['maritalStatus'],
            'maritalStatusId' => $this->getMaritalStatus()['maritalStatusId'],
            'employmentType' => $this->getEmploymentType()['employmentType'],
            'employmentTypeId' => $this->getEmploymentType()['employmentTypeId'],
            'employeeNumber' => $this->employeeNumber(),
            'dateOfBirth' => $this->getDateOfBirth(),
            'idNumber' => $this->getIdNumber(),
            'passportNumber' => $this->getPassportNumber(),
            'idType' => $this->gedIdType()['idType'],
            'idTypeId' => $this->gedIdType()['idTypeId'],
            'country' => $this->getCountry()['country'],
            'countryId' => $this->getCountry()['countryId'],
            'departments' => $this->getDepartments(),
        ];
    }

    private function getTitle(): array
    {
        $title = $this->has_student_profile ? $this->studentProfile?->title : $this->staffProfile?->title;
        return [
            'title' => $title?->name ?? null,
            'titleId' => $title?->id ?? null,
        ];
    }

    private function getGender(): array
    {
        $gender = $this->has_student_profile ? $this->studentProfile?->gender : $this->staffProfile?->gender;
        return [
            'gender' => $gender?->title ?? null,
            'genderId' => $gender?->id ?? null,
        ];
    }

    private function getMaritalStatus(): array
    {
        $status = $this->has_student_profile ? $this->studentProfile?->maritalStatus : $this->staffProfile?->maritalStatus;
        return [
            'maritalStatus' => $status?->title ?? null,
            'maritalStatusId' => $status?->id ?? null,
        ];
    }

    private function getEmploymentType(): array
    {
        $type = $this->has_staff_profile ? $this->staffProfile?->employmentType : null;
        return [
            'employmentType' => $type?->name ?? null,
            'employmentTypeId' => $type?->id ?? null,
        ];
    }

    private function employeeNumber(): string|null
    {
        return $this->has_staff_profile ? $this->staffProfile?->employee_number : null;
    }

    private function getDateOfBirth(): string|null
    {
        return $this->has_student_profile ? $this->studentProfile?->date_of_birth : $this->staffProfile?->date_of_birth;
    }

    private function getIdNumber(): string|null
    {
        return $this->has_student_profile ? $this->studentProfile?->id_number : $this->staffProfile?->id_number;
    }

    private function getPassportNumber(): string|null
    {
        return $this->has_student_profile ? $this->studentProfile?->passport_number : $this->staffProfile?->passport_number;
    }

    private function gedIdType(): array
    {
        $type = $this->has_staff_profile ? $this->staffProfile?->idType : $this->studentProfile?->idType;
        return [
            'idType' => $type?->name ?? null,
            'idTypeId' => $type?->id ?? null,
        ];
    }

    private function getCountry(): array
    {
        $country = $this->has_staff_profile ? $this->staffProfile?->country : $this->studentProfile?->country;
        return [
            'country' => $country?->name ?? null,
            'countryId' => $country?->id ?? null,
        ];
    }

    private function getDepartments(): array
    {
        $institutionDepartments = $this->has_staff_profile ? $this->staffProfile?->institutionDepartments : null;
        $mapped = $institutionDepartments?->map(function ($institutionDepartment) {
            return [
                'id' => $institutionDepartment->id,
                'name' => $institutionDepartment?->department->name ?? null,
            ];
        });
        return $mapped ? $mapped->toArray() : [];
    }
}
