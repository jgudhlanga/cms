<?php

namespace App\DTO\Students;

use App\Http\Requests\Students\CreateApplicationRequest;
use App\Http\Requests\Students\UpdateReturningApplicationRequest;
use App\Models\Institution\IntakePeriod;
use App\Models\Users\User;

readonly class CreateApplicationDto
{
    public function __construct(
        /** Personal details */
        public int $user_id,
        public string $id_type_id,
        public ?string $id_number,
        public ?string $passport_number,
        public ?int $country_id,
        public ?string $study_permit_number,
        public string $date_of_birth,
        public ?int $marital_status_id,
        public ?int $race_id,
        public string $email,
        public string $first_name,
        public ?string $middle_name,
        public string $last_name,
        public int $title_id,
        public int $gender_id,
        public ?string $disability_status,
        /** Contact details, addresses */
        public string $phone_number,
        public ?string $alt_phone_number,
        public string $address_1,
        public string $address_2,
        public string $address_3,
        public ?string $address_4,
        /** Next of kin details */
        public string $next_of_kin_name,
        public int $relationship_id,
        public string $next_of_kin_address_1,
        public string $next_of_kin_address_2,
        public string $next_of_kin_address_3,
        public ?string $next_of_kin_address_4,
        public string $next_of_kin_phone_number,
        /** Programs */
        public int $mode_of_study_id,
        public int $department_id,
        public int $level_id,
        public int $course_id,
        public ?bool $required_level_completed,
        public ?bool $read_write_acknowledged,
        public ?array $o_level_subject_ids,
        public ?array $o_level_years,
        public ?array $o_level_sittings,
        public ?array $o_level_other_subject_ids,
        public ?array $o_level_other_grade_ids,
        public ?array $o_level_other_years,
        public ?array $o_level_other_sittings,
        public int $intake_period_id,
    ) {}

    public static function fromCreateApplicationRequest(CreateApplicationRequest $request, User $user, IntakePeriod $intakePeriod): CreateApplicationDto
    {
        return self::fromApplicationRequest($request, $user, $intakePeriod);
    }

    public static function fromReturningApplicationRequest(
        UpdateReturningApplicationRequest $request,
        User $user,
        IntakePeriod $intakePeriod,
    ): CreateApplicationDto {
        return self::fromApplicationRequest($request, $user, $intakePeriod);
    }

    private static function fromApplicationRequest(
        CreateApplicationRequest $request,
        User $user,
        IntakePeriod $intakePeriod,
    ): CreateApplicationDto {
        return new self(
            /** Personal details */
            user_id: $user->id,
            id_type_id: $request->id_type_id,
            id_number: $request->id_number,
            passport_number: $request->passport_number,
            country_id: $request->country_id,
            study_permit_number: $request->study_permit_number,
            date_of_birth: $request->date_of_birth,
            marital_status_id: $request->marital_status_id,
            race_id: $request->race_id,
            email: $request->email,
            first_name: $request->first_name,
            middle_name: $request->middle_name,
            last_name: $request->last_name,
            title_id: $request->title_id,
            gender_id: $request->gender_id,
            disability_status: $request->disability_status,
            /** Contact details, addresses */
            phone_number: $request->phone_number,
            alt_phone_number: $request->alt_phone_number,
            address_1: $request->address_1,
            address_2: $request->address_2,
            address_3: $request->address_3,
            address_4: $request->address_4,
            /** Next of kin details */
            next_of_kin_name: $request->next_of_kin_name,
            relationship_id: $request->relationship_id,
            next_of_kin_address_1: $request->next_of_kin_address_1,
            next_of_kin_address_2: $request->next_of_kin_address_2,
            next_of_kin_address_3: $request->next_of_kin_address_3,
            next_of_kin_address_4: $request->next_of_kin_address_4,
            next_of_kin_phone_number: $request->next_of_kin_phone_number,
            /** Programs */
            mode_of_study_id: $request->mode_of_study_id,
            department_id: $request->department_id,
            level_id: $request->level_id,
            course_id: $request->course_id,
            required_level_completed: $request->required_level_completed,
            read_write_acknowledged: $request->read_write_acknowledged,
            o_level_subject_ids: $request->o_level_subject_ids,
            o_level_years: $request->o_level_years,
            o_level_sittings: $request->o_level_sittings,
            o_level_other_subject_ids: $request->o_level_other_subject_ids,
            o_level_other_grade_ids: $request->o_level_other_grade_ids,
            o_level_other_years: $request->o_level_other_years,
            o_level_other_sittings: $request->o_level_other_sittings,
            intake_period_id: $intakePeriod->id,
        );
    }
}
