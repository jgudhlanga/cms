<?php

namespace App\DTO\Institution;

use App\Http\Requests\Institution\CreateStaffRequest;
use App\Http\Requests\Students\CreateApplicationRequest;
use App\Models\Users\User;

readonly class CreateStaffDto
{
    public function __construct(
        public int  $institution_department_id,
        public string  $date_of_birth,
        public ?int    $marital_status_id,
        public ?int    $race_id,
        public string  $email,
        public ?string  $phone_number,
        public string  $first_name,
        public ?string $middle_name,
        public string  $last_name,
        public int     $title_id,
        public int     $gender_id,
        public ?array  $role_ids,
        public ?int $employment_type_id,
        public string $employee_number,
    )
    {
    }

    public static function fromStaffRequest(CreateStaffRequest $request): CreateStaffDto
    {
        return new self(
        /** Personal details */
            institution_department_id: $request->institution_department_id,
            date_of_birth: $request->date_of_birth,
            marital_status_id: $request->marital_status_id,
            race_id: $request->race_id,
            email: $request->email,
            phone_number: $request->phone_number,
            first_name: $request->first_name,
            middle_name: $request->middle_name,
            last_name: $request->last_name,
            title_id: $request->title_id,
            gender_id: $request->gender_id,
            role_ids: $request->role_ids,
            employment_type_id: $request->employment_type_id,
            employee_number: $request->employee_number,
        );
    }
}
