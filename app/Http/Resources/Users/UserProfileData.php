<?php

namespace App\Http\Resources\Users;

use App\Models\Users\User;

class UserProfileData
{
    public static function forUser(User $user): array
    {
        $instance = new self($user);
        return $instance->toArray();
    }

    public function __construct(
        private readonly User $user
    ) {
    }

    public function toArray(): array
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
            'idType' => $this->getIdType()['idType'],
            'idTypeId' => $this->getIdType()['idTypeId'],
            'country' => $this->getCountry()['country'],
            'countryId' => $this->getCountry()['countryId'],
            'departments' => $this->getDepartments(),
        ];
    }

    private function getTitle(): array
    {
        $title = $this->user->has_student_profile
            ? $this->user->studentProfile?->title
            : $this->user->staffProfile?->title;
        return [
            'title' => $title?->name ?? null,
            'titleId' => $title?->id ?? null,
        ];
    }

    private function getGender(): array
    {
        $gender = $this->user->has_student_profile
            ? $this->user->studentProfile?->gender
            : $this->user->staffProfile?->gender;
        return [
            'gender' => $gender?->title ?? null,
            'genderId' => $gender?->id ?? null,
        ];
    }

    private function getMaritalStatus(): array
    {
        $status = $this->user->has_student_profile
            ? $this->user->studentProfile?->maritalStatus
            : $this->user->staffProfile?->maritalStatus;
        return [
            'maritalStatus' => $status?->title ?? null,
            'maritalStatusId' => $status?->id ?? null,
        ];
    }

    private function getEmploymentType(): array
    {
        $type = $this->user->has_staff_profile ? $this->user->staffProfile?->employmentType : null;
        return [
            'employmentType' => $type?->name ?? null,
            'employmentTypeId' => $type?->id ?? null,
        ];
    }

    private function employeeNumber(): ?string
    {
        return $this->user->has_staff_profile ? $this->user->staffProfile?->employee_number : null;
    }

    private function getDateOfBirth(): ?string
    {
        return $this->user->has_student_profile
            ? $this->user->studentProfile?->date_of_birth
            : $this->user->staffProfile?->date_of_birth;
    }

    private function getIdNumber(): ?string
    {
        return $this->user->has_student_profile
            ? $this->user->studentProfile?->id_number
            : $this->user->staffProfile?->id_number;
    }

    private function getPassportNumber(): ?string
    {
        return $this->user->has_student_profile
            ? $this->user->studentProfile?->passport_number
            : $this->user->staffProfile?->passport_number;
    }

    private function getIdType(): array
    {
        $type = $this->user->has_staff_profile
            ? $this->user->staffProfile?->idType
            : $this->user->studentProfile?->idType;
        return [
            'idType' => $type?->name ?? null,
            'idTypeId' => $type?->id ?? null,
        ];
    }

    private function getCountry(): array
    {
        $country = $this->user->has_staff_profile
            ? $this->user->staffProfile?->country
            : $this->user->studentProfile?->country;
        return [
            'country' => $country?->name ?? null,
            'countryId' => $country?->id ?? null,
        ];
    }

    private function getDepartments(): array
    {
        $institutionDepartments = $this->user->has_staff_profile
            ? $this->user->staffProfile?->institutionDepartments
            : null;
        $mapped = $institutionDepartments?->map(function ($institutionDepartment) {
            return [
                'id' => $institutionDepartment->id,
                'name' => $institutionDepartment?->department->name ?? null,
            ];
        });
        return $mapped ? $mapped->toArray() : [];
    }
}
