<?php

namespace App\Enums\Institution;

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

    public function id(): string
    {
        return match ($this) {
            self::A => 1,
            self::B => 2,
            self::C => 3,
            self::D => 4,
            self::E => 5,
            self::U => 6,
        };
    }

    public function position(): string
    {
        return match ($this) {
            self::A => 1,
            self::B => 2,
            self::C => 3,
            self::D => 4,
            self::E => 5,
            self::U => 6,
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
