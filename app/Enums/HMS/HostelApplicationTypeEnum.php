<?php

namespace App\Enums\HMS;

enum HostelApplicationTypeEnum: string
{
    case STUDENT = 'student';
    case GUEST = 'guest';

    public function label(): string
    {
        return match ($this) {
            self::STUDENT => __('hms.application_type_student'),
            self::GUEST => __('hms.application_type_guest'),
        };
    }
}
