<?php

namespace App\DTO\Institution;

use App\Http\Requests\Institution\DepartmentLevelRequirementRequest;

readonly class DepartmentLevelRequirementsDto
{
    public function __construct(
        public bool    $is_o_level_required,
        public ?int    $required_subjects_count,
        public ?int    $main_subjects_count,
        public array   $main_subject_ids,
        public ?int    $other_subjects_count,
        public bool    $only_read_write_required,
        public bool    $is_previous_level_required,
        public ?string $previous_level_id,

    )
    {
    }


    public static function fromDepartmentLevelRequirementRequest(DepartmentLevelRequirementRequest $request): DepartmentLevelRequirementsDto
    {
        return new self(
            is_o_level_required: $request->is_o_level_required,
            required_subjects_count: $request->required_subjects_count,
            main_subjects_count: $request->main_subjects_count,
            main_subject_ids: $request->main_subject_ids,
            other_subjects_count: $request->other_subjects_count,
            only_read_write_required: $request->only_read_write_required,
            is_previous_level_required: $request->is_previous_level_required,
            previous_level_id: $request->previous_level_id,
        );
    }
}
