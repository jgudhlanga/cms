<?php

declare(strict_types=1);

namespace App\Enums\Setup;

/**
 * Every setup problem the nightly scan knows how to detect. The value is stored on setup_gaps and used
 * in the notification payload, so cases are renamed only alongside a data migration.
 */
enum SetupGapCheckEnum: string
{
    // Programme configuration
    case APPLICATIONS_IN_UNCONFIGURED_MODE = 'applications_in_unconfigured_mode';
    case COURSE_LEVEL_WITHOUT_MODES = 'course_level_without_modes';
    case MODES_ON_UNLINKED_LEVEL = 'modes_on_unlinked_level';
    case APPLICATIONS_MISSING_MODE_OR_LEVEL = 'applications_missing_mode_or_level';

    // Coursework / assessment configuration
    case DEPARTMENT_ASSESSMENT_CALENDAR_MISSING = 'department_assessment_calendar_missing';
    case ASSESSMENT_CALENDAR_DATES_MISSING = 'assessment_calendar_dates_missing';
    case CLASS_CONFIG_WITHOUT_LECTURER_IN_CHARGE = 'class_config_without_lecturer_in_charge';
    case CLASS_CONFIG_WITHOUT_SYLLABUS = 'class_config_without_syllabus';

    // Organisation structure — these break department scoping everywhere, not just alerts
    case DIVISION_WITHOUT_HEAD = 'division_without_head';
    case DEPARTMENTS_WITHOUT_DIVISION = 'departments_without_division';

    // Accommodation
    case HOSTEL_BEDS_VACANT_WITH_WAITING_APPLICANTS = 'hostel_beds_vacant_with_waiting_applicants';

    public function severity(): SetupGapSeverityEnum
    {
        return match ($this) {
            self::APPLICATIONS_IN_UNCONFIGURED_MODE,
            self::COURSE_LEVEL_WITHOUT_MODES,
            self::APPLICATIONS_MISSING_MODE_OR_LEVEL => SetupGapSeverityEnum::CRITICAL,

            self::DEPARTMENT_ASSESSMENT_CALENDAR_MISSING,
            self::CLASS_CONFIG_WITHOUT_LECTURER_IN_CHARGE,
            self::ASSESSMENT_CALENDAR_DATES_MISSING,
            self::CLASS_CONFIG_WITHOUT_SYLLABUS,
            self::DIVISION_WITHOUT_HEAD => SetupGapSeverityEnum::WARNING,

            self::MODES_ON_UNLINKED_LEVEL,
            self::DEPARTMENTS_WITHOUT_DIVISION,
            self::HOSTEL_BEDS_VACANT_WITH_WAITING_APPLICANTS => SetupGapSeverityEnum::INFO,
        };
    }

    /**
     * The ability needed to act on this kind of gap — the same permission the screen that fixes it
     * requires. Only people holding it are shown the gap or notified about it, so nobody is told about
     * a problem they cannot do anything about.
     *
     * Note: the department assessment calendar abilities are granted to Super User, Head of Division and
     * Head of Department; VP Academics deliberately owns the global calendar instead.
     */
    public function permission(): string
    {
        return match ($this) {
            // Course level modes are edited on the department page.
            self::APPLICATIONS_IN_UNCONFIGURED_MODE,
            self::COURSE_LEVEL_WITHOUT_MODES,
            self::MODES_ON_UNLINKED_LEVEL => 'update:department-metadata',

            // Fixed on the application itself, not in configuration.
            self::APPLICATIONS_MISSING_MODE_OR_LEVEL => 'update:student-applications',

            self::DEPARTMENT_ASSESSMENT_CALENDAR_MISSING => 'create:department-assessment-calendar',
            self::ASSESSMENT_CALENDAR_DATES_MISSING => 'update:assessment-calendar',

            // Both are fixed on the department classes screen, which authorises against the calendar.
            self::CLASS_CONFIG_WITHOUT_LECTURER_IN_CHARGE,
            self::CLASS_CONFIG_WITHOUT_SYLLABUS => 'update:academic-calendars',

            // Organisation structure is owned college-wide, not by the departments themselves.
            self::DIVISION_WITHOUT_HEAD => 'update:divisions',
            self::DEPARTMENTS_WITHOUT_DIVISION => 'update:departments',

            self::HOSTEL_BEDS_VACANT_WITH_WAITING_APPLICANTS => 'update:hostels',
        };
    }

    public function label(): string
    {
        return __('setup_gaps.check_'.$this->value);
    }
}
