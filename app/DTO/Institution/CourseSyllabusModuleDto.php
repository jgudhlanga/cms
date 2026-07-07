<?php

namespace App\DTO\Institution;

use App\Http\Requests\Institution\CourseSyllabusModuleRequest;

readonly class CourseSyllabusModuleDto
{
    public function __construct(
        public int $course_syllabus_id,
        public int $academic_year_option_id,
        public string $title,
        public string $code,
        public ?int $duration_in_hours,
        public ?int $nql_level,
        /** @var array<int>|null */
        public ?array $prerequisite_module_ids,
        public bool $shared,
        public bool $all_semesters,
        public bool $capture_mark_only,
        /** @var array<int> */
        public array $staff_ids,
    ) {}

    public static function fromRequest(CourseSyllabusModuleRequest $request): self
    {
        return new self(
            course_syllabus_id: (int) $request->integer('course_syllabus_id'),
            academic_year_option_id: (int) $request->integer('academic_year_option_id'),
            title: $request->string('title')->toString(),
            code: $request->string('code')->toString(),
            duration_in_hours: $request->filled('duration_in_hours') ? $request->integer('duration_in_hours') : null,
            nql_level: $request->filled('nql_level') ? $request->integer('nql_level') : null,
            prerequisite_module_ids: $request->filled('prerequisite_module_ids')
                ? array_map('intval', $request->input('prerequisite_module_ids', []))
                : null,
            shared: $request->boolean('shared'),
            all_semesters: $request->boolean('all_semesters'),
            capture_mark_only: $request->boolean('capture_mark_only'),
            staff_ids: array_map('intval', $request->input('staff_ids', [])),
        );
    }
}
