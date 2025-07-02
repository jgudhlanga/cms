<?php

namespace App\Enums\Shared;

enum EmploymentTypeEnum: string
{
    case FULL_TIME = 'Full time';
    case PART_TIME = 'Part time';
    case TEMPORARY = 'Temporary';
    case FREELANCE = 'Freelance';
    case INTERN = 'Intern';
    case CASUAL = 'Casual';
    case SEASONAL = 'Seasonal';
    case REMOTE = 'Remote';

    public function description(): string
    {
        return match ($this) {
            self::FULL_TIME => 'Full-time employment (35–40+ hours/week with benefits)',
            self::PART_TIME => 'Part-time employment (less than 35 hours/week)',
            self::TEMPORARY => 'Temporary or contract-based employment',
            self::FREELANCE => 'Freelance or self-employed contractor work',
            self::INTERN => 'Internship or apprenticeship (temporary, for experience)',
            self::CASUAL => 'Casual work (on-call or irregular hours)',
            self::SEASONAL => 'Seasonal employment (e.g. holiday or harvest periods)',
            self::REMOTE => 'Remote or telecommuting work (offsite)',
        };
    }

    public static function options(): array
    {
        return array_map(
            fn(self $type) => [
                'value' => $type->value,
                'label' => $type->description(),
            ],
            self::cases()
        );
    }
}
