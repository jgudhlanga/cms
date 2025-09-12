<?php

namespace App\Enums\Institution;

enum LevelEnum: string
{
    case ABMA_LEVEL_3 = "ABMA Level 3";
    case ABMA_LEVEL_4 = "ABMA Level 4";
    case ABMA_LEVEL_5 = "ABMA Level 5";
    case ABMA_LEVEL_6 = "ABMA Level 6";
    case NC = 'NC';
    case ND = 'ND';
    case HND = 'HND';
    case BTECH = 'BTECH';
    case SDP = 'SDP';

    public function name(): string
    {
        return match ($this) {
            self::NC => 'NC',
            self::ND => 'ND',
            self::HND => 'HND',
            self::BTECH => 'BTECH',
            self::SDP => 'SDP',
            self::ABMA_LEVEL_3 => 'ABMA Level 3',
            self::ABMA_LEVEL_4 => 'ABMA Level 4',
            self::ABMA_LEVEL_5 => 'ABMA Level 5',
            self::ABMA_LEVEL_6 => 'ABMA Level 6',
        };
    }

    public function description(): string
    {
        return match ($this) {
            self::NC => 'National Certificate',
            self::ND => 'National Diploma',
            self::HND => 'Higher National Diploma',
            self::BTECH => 'Bachelor of Technology',
            self::SDP => 'Skills Development Program',
            self::ABMA_LEVEL_3 => 'Association of Business Managers and Administrators - 3',
            self::ABMA_LEVEL_4 => 'Association of Business Managers and Administrators - 4',
            self::ABMA_LEVEL_5 => 'Association of Business Managers and Administrators - 5',
            self::ABMA_LEVEL_6 => 'Association of Business Managers and Administrators - 6',
        };
    }

    public function position(): string
    {
        return match ($this) {
            self::ABMA_LEVEL_3 => 1,
            self::ABMA_LEVEL_4 => 2,
            self::ABMA_LEVEL_5 => 3,
            self::ABMA_LEVEL_6 => 4,
            self::NC => 5,
            self::ND => 6,
            self::HND => 7,
            self::BTECH => 8,
            self::SDP => 9,
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
