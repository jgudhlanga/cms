<?php

namespace App\Enums\Acl;

enum RoleGroupEnum: string
{
    case SUPER_USER = 'super-user';
    case TESC = 'tesc';
    case EXECUTIVE = 'executive';
    case ACADEMIC = 'academic';
    case ADMINISTRATIVE_MANAGERIAL = 'administrative-and-managerial';
    case SERVICE_AND_SUPPORT = 'service-and-support';
    case STUDENT = 'student';

    public function name(): string
    {
        return match ($this) {
            self::SUPER_USER => 'Super User',
            self::TESC => 'TESC',
            self::EXECUTIVE => 'Executive',
            self::ACADEMIC => 'Academic',
            self::ADMINISTRATIVE_MANAGERIAL => 'Administrative and Managerial',
            self::SERVICE_AND_SUPPORT => 'Service and support',
            self::STUDENT => 'Student',
        };
    }

    public function description(): string
    {
        return match ($this) {
            self::SUPER_USER => 'System-level user with access to all areas.',
            self::TESC => 'Tertiary Education Service Council (TESC) group.',
            self::EXECUTIVE => 'Executive leadership including deans, directors, or heads of departments.',
            self::ACADEMIC => 'Teaching and research personnel such as lecturers and professors.',
            self::ADMINISTRATIVE_MANAGERIAL => 'Administrative Staff (Non-Academic, Managerial) involved in management or administration.',
            self::SERVICE_AND_SUPPORT => 'Support and Service Staff (Non-Academic, Operational) providing technical, clerical, or facility-related support.',
            self::STUDENT => 'Registered learners in the institution.',
        };
    }

    public static function options(): array
    {
        return array_map(
            fn(self $type) => [
                'value' => $type->value,
                'name' => $type->name(),
                'description' => $type->description(),
            ],
            self::cases()
        );
    }
}

