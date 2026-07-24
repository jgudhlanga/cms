<?php

namespace App\Enums\Shared;

enum ModuleEnum: string
{
    case RBAC = 'Rbac';
    case COMMUNICATIONS = 'Communications';
    case DASHBOARDS = 'Dashboards';
    case ENROLMENTS = 'Enrolments';
    case EXAMINATIONS = 'Examinations';
    case INSTITUTION = 'Institution';
    case OTHER = 'Other';
    case REPORTS = 'Reports';
    case ROOT = 'Root';
    case SETTINGS = 'Settings';
    case SHARED = 'Shared';
    case STUDENTS = 'Students';
    case TENANTS = 'Tenants';
    case USERS = 'Users';
    case FINANCE = 'Finance';
    case HMS = 'HMS';
    case COURSE_WORK = 'Course Work';

    public function label(): string
    {
        return match ($this) {
            self::RBAC => 'Rbac',
            self::COMMUNICATIONS => 'Communications',
            self::DASHBOARDS => 'Dashboards',
            self::ENROLMENTS => 'Enrolments',
            self::EXAMINATIONS => 'Examinations',
            self::INSTITUTION => 'Institution',
            self::OTHER => 'Other',
            self::REPORTS => 'Reports',
            self::ROOT => 'Root',
            self::SETTINGS => 'Settings',
            self::SHARED => 'Shared',
            self::STUDENTS => 'Students',
            self::TENANTS => 'Tenants',
            self::USERS => 'Users',
            self::FINANCE => 'Finance',
            self::HMS => 'HMS',
            self::COURSE_WORK => 'Course Work',
        };
    }

    public static function all(): array
    {
        return array_combine(
            array_column(self::cases(), 'value'),
            array_map(fn ($case) => $case->label(), self::cases())
        );
    }
}
