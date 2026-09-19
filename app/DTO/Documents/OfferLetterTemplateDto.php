<?php

declare(strict_types=1);

namespace App\DTO\Documents;

use App\Http\Requests\Documents\OfferLetterTemplateRequest;

readonly class OfferLetterTemplateDto
{
    /**
     * @param  list<int>  $institution_department_ids
     * @param  list<int>  $level_ids
     */
    public function __construct(
        public string $name,
        public ?string $helper_description,
        public array $institution_department_ids,
        public array $level_ids,
        public ?int $mode_of_study_id,
        public ?int $course_id,
        public ?string $tuition_override,
        public ?string $header_line_1,
        public ?string $header_line_2,
        public ?string $header_address_line_1,
        public ?string $header_address_line_2,
        public ?string $header_telephone,
        public ?string $header_email,
        public ?string $header_website,
        public ?string $body,
    ) {}

    public static function fromRequest(OfferLetterTemplateRequest $request): self
    {
        return new self(
            name: (string) $request->name,
            helper_description: $request->helper_description,
            institution_department_ids: array_values(array_unique(array_map(
                'intval',
                $request->input('institution_department_ids', []),
            ))),
            level_ids: array_values(array_unique(array_map('intval', $request->input('level_ids', [])))),
            mode_of_study_id: self::nullableInt($request->mode_of_study_id),
            course_id: self::nullableInt($request->course_id),
            tuition_override: $request->filled('tuition_override') ? (string) $request->tuition_override : null,
            header_line_1: $request->header_line_1,
            header_line_2: $request->header_line_2,
            header_address_line_1: $request->header_address_line_1,
            header_address_line_2: $request->header_address_line_2,
            header_telephone: $request->header_telephone,
            header_email: $request->header_email,
            header_website: $request->header_website,
            body: $request->body,
        );
    }

    private static function nullableInt(mixed $value): ?int
    {
        if ($value === null || $value === '' || (int) $value < 1) {
            return null;
        }

        return (int) $value;
    }
}
