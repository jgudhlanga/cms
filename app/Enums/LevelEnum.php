<?php

namespace App\Enums;

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

    public function label(): string
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

    public static function all(): array
    {
        return array_combine(
            array_column(self::cases(), 'value'),
            array_map(fn($case) => $case->label(), self::cases())
        );
    }
}
