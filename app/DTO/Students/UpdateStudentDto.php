<?php

namespace App\DTO\Students;

use App\Http\Requests\Students\UpdateStudentRequest;

readonly class UpdateStudentDto
{
    public function __construct(
        public string  $id_type_id,
        public ?string $id_number,
        public ?string $passport_number,
        public ?int    $country_id,
        public string  $date_of_birth,
        public ?int    $marital_status_id,
        public ?int    $race_id,
        public int     $title_id,
        public int     $gender_id,
        public ?int    $religion_id,
        public ?string $denomination,
        public ?string $height,
        public ?string $weight,
        public ?string $study_permit_number,
    )
    {
    }

    public static function fromUpdateStudentRequest(UpdateStudentRequest $request): UpdateStudentDto
    {
        return new self(
            id_type_id: $request->id_type_id,
            id_number: $request->id_number,
            passport_number: $request->passport_number,
            country_id: $request->country_id,
            date_of_birth: $request->date_of_birth,
            marital_status_id: $request->marital_status_id,
            race_id: $request->race_id,
            title_id: $request->title_id,
            gender_id: $request->gender_id,
            religion_id: $request->religion_id,
            denomination: $request->denomination,
            height: $request->height,
            weight: $request->weight,
            study_permit_number: $request->study_permit_number,
        );
    }
}
