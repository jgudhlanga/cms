<?php

namespace App\Support\Dashboard;

enum DashboardTab: string
{
    case Overview = 'overview';
    case Academic = 'academic';
    case Enrolments = 'enrolments';
    case Attendance = 'attendance';
    case Staff = 'staff';
    case Finance = 'finance';
    case Hostel = 'hostel';

    public function permission(): string
    {
        return match ($this) {
            self::Overview => 'view:dashboards',
            self::Academic => 'view-academic:dashboards',
            self::Enrolments => 'view-enrolment:dashboards',
            self::Attendance => 'view-attendance:dashboards',
            self::Staff => 'view-staff:dashboards',
            self::Finance => 'view-finance:dashboards',
            self::Hostel => 'view-hostel:dashboards',
        };
    }

    /**
     * @return array<string, string>
     */
    public static function permissionMap(): array
    {
        $map = [];

        foreach (self::cases() as $tab) {
            $map[$tab->value] = $tab->permission();
        }

        return $map;
    }

    /**
     * @return array<string, bool>
     */
    public static function defaultTabSettings(): array
    {
        $defaults = [];

        foreach (self::cases() as $tab) {
            $defaults[$tab->value] = true;
        }

        return $defaults;
    }
}
