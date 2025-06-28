<?php

namespace App\Enums\Shared;

enum ModuleEnum: string
{

    case ACCOMMODATIONS = 'Accommodations';
    case ACL = 'Acl';
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

    public function label(): string
    {
        return match ($this) {
            self::ACCOMMODATIONS => 'Accommodations',
            self::ACL => 'Acl',
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

