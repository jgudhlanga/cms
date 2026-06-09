<?php

declare(strict_types=1);

namespace App\DTO\Institution;

readonly class StaffImportRowDto
{
    /**
     * @param  list<string>  $roleSlugs
     */
    public function __construct(
        public int $tenantId,
        public string $employeeNumber,
        public int $titleId,
        public string $firstName,
        public ?string $middleName,
        public string $lastName,
        public int $genderId,
        public int $maritalStatusId,
        public int $employmentTypeId,
        public string $dateOfBirth,
        public string $email,
        public string $phoneNumber,
        public int $institutionDepartmentId,
        public array $roleSlugs = [],
        public ?string $idNumber = null,
        public ?string $passportNumber = null,
        public ?string $altPhoneNumber = null,
        public ?string $altEmailAddress = null,
        public ?string $address1 = null,
        public ?string $address2 = null,
        public ?string $address3 = null,
        public ?string $address4 = null,
    ) {}
}
