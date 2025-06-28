<?php

namespace App\Enums\Shared;

enum SponsorTypeEnum: string
{
    case PERSON = 'Person';
    case COMPANY = 'Company';
    case CHURCH = 'Church';
    case OTHER_ORGANIZATION = 'Other Organization';


    public function label(): string
    {
        return match ($this) {
            self::PERSON => 'Person',
            self::COMPANY => 'Company',
            self::CHURCH => 'Church',
            self::OTHER_ORGANIZATION => 'Other Organization',
        };
    }

    public static function all(): array
    {
        return array_combine(
            array_column(self::cases(), 'value'),
            array_map(fn($case) => $case->label(), self::cases())
        );
    }
}
