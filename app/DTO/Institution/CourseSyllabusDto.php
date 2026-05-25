<?php

namespace App\DTO\Institution;

use App\Enums\Institution\CourseSyllabusStatusEnum;
use App\Http\Requests\Institution\CourseSyllabusRequest;

readonly class CourseSyllabusDto
{
    public function __construct(
        public int $institution_department_id,
        public int $department_level_course_id,
        public string $title,
        public string $code,
        public string $implementation_year,
        public CourseSyllabusStatusEnum $status,
    ) {}

    public static function fromRequest(CourseSyllabusRequest $request): self
    {
        return new self(
            institution_department_id: (int) $request->institution_department_id,
            department_level_course_id: (int) $request->department_level_course_id,
            title: $request->title,
            code: $request->code,
            implementation_year: $request->implementation_year,
            status: CourseSyllabusStatusEnum::from($request->input('status')),
        );
    }
}
