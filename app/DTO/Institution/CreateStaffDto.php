<?php

namespace App\DTO\Institution;

use App\Http\Requests\Institution\CreateStaffRequest;
use App\Http\Requests\Students\CreateApplicationRequest;
use App\Models\Users\User;

readonly class CreateStaffDto
{
    public function __construct(
        public int     $user_id,
        public string  $id_type,
        public ?string $id_number,
        public ?string $passport_number,
        public ?int    $country_id,
        public ?string $work_permit_number,
        public string  $date_of_birth,
        public ?int    $marital_status_id,
        public ?int    $race_id,
        public string  $email,
        public string  $first_name,
        public ?string $middle_name,
        public string  $last_name,
        public int     $title_id,
        public int     $gender_id,
        public ?array  $role_ids,
        public ?array  $institution_department_id,
    )
    {
    }

    public static function fromStaffRequest(CreateStaffRequest $request, User $user): CreateStaffDto
    {
        return new self(
        /** Personal details */
            user_id: $user->id,
            id_type: $request->id_type,
            id_number: $request->id_number,
            passport_number: $request->passport_number,
            country_id: $request->country_id,
            work_permit_number: $request->work_permit_number,
            date_of_birth: $request->date_of_birth,
            marital_status_id: $request->marital_status_id,
            race_id: $request->race_id,
            email: $request->email,
            first_name: $request->first_name,
            middle_name: $request->middle_name,
            last_name: $request->last_name,
            title_id: $request->title_id,
            gender_id: $request->gender_id,
            role_ids: $request->role_ids,
            institution_department_id: $request->institution_department_id,
        );
    }
}
