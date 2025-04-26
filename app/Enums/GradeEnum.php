<?php

namespace App\Enums;

enum GradeEnum: string
{
    case A = "A";
    case B = "B";
    case C = "C";
    case D = "D";
    case E = "E";
    case U = "U";

    public function label(): string
    {
        return match ($this) {
            self::A => "A",
            self::B => "B",
            self::C => "C",
            self::D => "D",
            self::E => "E",
            self::U => "U",
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
