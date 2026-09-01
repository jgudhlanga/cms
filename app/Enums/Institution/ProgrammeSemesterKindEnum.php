<?php

declare(strict_types=1);

namespace App\Enums\Institution;

enum ProgrammeSemesterKindEnum: string
{
    case TAUGHT = 'taught';
    case INDUSTRIAL_ATTACHMENT = 'industrial_attachment';

    public function label(): string
    {
        return match ($this) {
            self::TAUGHT => 'Taught',
            self::INDUSTRIAL_ATTACHMENT => 'Industrial attachment',
        };
    }
}
