<?php

namespace App\Enums\Institution;

enum CourseSyllabusStatusEnum: string
{
    case Active = 'active';
    case Terminated = 'terminated';

    public function label(): string
    {
        return match ($this) {
            self::Active => 'Active',
            self::Terminated => 'Terminated',
        };
    }
}
