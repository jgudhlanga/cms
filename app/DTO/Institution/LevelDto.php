<?php

namespace App\DTO\Institution;

use App\Http\Requests\Institution\LevelRequest;

readonly class LevelDto
{
    public function __construct(
        public string $name,
        public ?string $description,
        public ?int $allowed_applications_per_level,
        public bool $show_on_current_application_period,
        public bool $has_application_fee_payment,
        public string $calendar_type,
    ) {}

    public static function fromLevelRequest(LevelRequest $request): LevelDto
    {
        return new self(
            name: $request->name,
            description: $request->description,
            allowed_applications_per_level: $request->allowed_applications_per_level,
            show_on_current_application_period: $request->show_on_current_application_period,
            has_application_fee_payment: $request->has_application_fee_payment,
            calendar_type: $request->calendar_type,
        );
    }
}
