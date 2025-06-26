<?php

namespace App\Enums\Shared;

enum AcademicLevelEnum: string
{
    case PRIMARY_SCHOOL = 'Primary school';
    case SECONDARY_SCHOOL = 'Secondary school';
    case ADVANCED_LEVEL = 'Advanced Level';
    case TERTIARY_LEVEL = 'Tertiary Level';


    public function label(): string
    {
        return match ($this) {
            self::PRIMARY_SCHOOL => 'Primary School',
            self::SECONDARY_SCHOOL => 'Secondary School',
            self::ADVANCED_LEVEL => 'Advanced Level',
            self::TERTIARY_LEVEL => 'Tertiary Level',
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
