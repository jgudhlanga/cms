<?php

namespace App\Support\Dashboard;

use LogicException;

enum DashboardTab: string
{
    case Overview = 'overview';
    case Academic = 'academic';
    case Enrolments = 'enrolments';
    case Attendance = 'attendance';
    case Staff = 'staff';
    case Finance = 'finance';
    case Hostel = 'hostel';
    case Activity = 'activity';

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
            self::Activity => throw new LogicException('Activity tab has no permission.'),
        };
    }

    public function requiresPermission(): bool
    {
        return $this !== self::Activity;
    }

    /**
     * @return array<string, string>
     */
    public static function permissionMap(): array
    {
        $map = [];

        foreach (self::cases() as $tab) {
            if (! $tab->requiresPermission()) {
                continue;
            }

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
