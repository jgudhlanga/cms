<?php

namespace App\Enums\Institution;

enum ModeOfStudyEnum: string
{
    case FULL_TIME = "Full Time";
    case PART_TIME = "Part Time";
    case BLOCK_RELEASE = "Block Release";
    case OJET = "Ojet";

    public function label(): string
    {
        return match ($this) {
            self::FULL_TIME => 'Full Time',
            self::PART_TIME => 'Part Time',
            self::BLOCK_RELEASE => 'Block Release',
            self::OJET => 'Ojet',
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
