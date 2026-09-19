<?php

return [
    // Header alert
    'title' => 'Setup issues',
    'open_count' => 'Setup issues, :count open',
    'empty' => 'No setup issues. Everything is configured.',
    'loading' => 'Loading setup issues…',
    'load_failed' => 'Setup issues could not be loaded. Try again shortly.',
    'view_all' => 'View all',
    'severity_critical' => 'Critical',
    'severity_warning' => 'Warning',
    'severity_info' => 'For information',
    'detected_on' => 'Found :date',
    'refresh' => 'Refresh',
    'refresh_one' => 'Re-check this issue',
    'refreshing' => 'Checking…',
    'refresh_failed' => 'Could not re-check just now. Try again shortly.',

    // Check names
    'check_applications_in_unconfigured_mode' => 'Applications in a mode that is not set up',
    'check_course_level_without_modes' => 'Course level with no modes of study',
    'check_modes_on_unlinked_level' => 'Modes set up on an unlinked level',
    'check_applications_missing_mode_or_level' => 'Applications with no mode or level',
    'check_department_assessment_calendar_missing' => 'Department has no assessment calendar',
    'check_assessment_calendar_dates_missing' => 'Assessment calendar has no reminder dates',
    'check_class_config_without_lecturer_in_charge' => 'Class with no lecturer in charge',
    'check_class_config_without_syllabus' => 'Class with no modules',
    'check_hostel_beds_vacant_with_waiting_applicants' => 'Vacant beds while applicants wait',
    'check_division_without_head' => 'Division has no head',
    'check_departments_without_division' => 'Departments not linked to a division',
    'check_offering_mode_not_configured' => 'Online applications offer an unset-up mode',
    'check_offering_course_level_unlinked' => 'Online applications offer an unlinked course level',
    'check_offering_references_deleted_record' => 'Online applications offer a deleted course or level',
    'check_intake_period_missing_offer_letters' => 'Intake period has no offer letters',

    // Gap titles and detail lines
    'applications_in_unconfigured_mode_title' => ':course :level — :mode is not set up',
    'applications_in_unconfigured_mode_body' => ':count application(s) are in :mode, but :course :level is only set up for :configured. They will not appear in the class lists until the mode is added.',
    'applications_in_unconfigured_mode_body_none' => ':count application(s) are in :mode, but :course :level has no modes set up at all.',

    'course_level_without_modes_title' => ':course :level has no modes of study',
    'course_level_without_modes_body' => ':count application(s) sit on this course level, but no mode of study has been set up for it.',

    'modes_on_unlinked_level' => 'Modes are set up for :course :level, but that level is not linked to the course.',
    'modes_on_unlinked_level_title' => ':course :level is not linked to the course',

    'applications_missing_mode_or_level_title' => ':count application(s) have no :missing',
    'applications_missing_mode_or_level_body' => 'These applications cannot be placed on a class list until the missing details are set.',
    'missing_mode_of_study' => 'mode of study',
    'missing_level' => 'level',
    'missing_mode_and_level' => 'mode of study or level',

    'department_assessment_calendar_missing_title' => ':department has no assessment calendar for :calendar',
    'department_assessment_calendar_missing_body' => 'The department teaches :count class(es) this period. Without its own calendar, assessment windows and mark reminders never run.',

    'assessment_calendar_dates_missing_title' => ':assessment calendar has no reminder dates',
    'assessment_calendar_dates_missing_body' => 'First, second and due reminder days are unset, so missing-marks reminders will not be sent.',

    'class_config_without_lecturer_in_charge_title' => ':class has no lecturer in charge',
    'class_config_without_lecturer_in_charge_body' => 'Coursework capture for this class has no owner until a lecturer in charge is assigned.',

    'class_config_without_syllabus_title' => ':class has no modules',
    'class_config_without_syllabus_body' => 'No course syllabus is attached, so there is nothing to capture marks against.',

    'hostel_beds_vacant_with_waiting_applicants_title' => ':hostel has :beds vacant bed(s) with :applicants applicant(s) waiting',
    'hostel_beds_vacant_with_waiting_applicants_body' => 'Approved applicants have not been placed although beds are free.',

    'division_without_head_title' => ':division has no head of division',
    'division_without_head_body' => 'Until a head is assigned, anyone holding the Head of Division role for :division is scoped down to their own department and cannot see the rest of the division.',

    'departments_without_division_title' => ':count department(s) are not linked to a division',
    'departments_without_division_body' => 'Division scoping and division reporting skip these departments: :departments',

    'offering_mode_not_configured_title' => ':course :level — applicants can select :mode, which is not set up',
    'offering_mode_not_configured_body' => 'The online application catalogue offers :mode for :course :level, but the department has only set up :configured. Anyone who applies in :mode will end up in the same unconfigured state as an application entered directly.',
    'offering_mode_not_configured_body_none' => 'The online application catalogue offers :mode for :course :level, but the department has not set up any modes for it at all.',

    'offering_course_level_unlinked_title' => ':course :level is offered to applicants but no longer linked',
    'offering_course_level_unlinked_body' => 'The online application catalogue still lists :course at :level, but the department has since unlinked that course from that level. Applicants can still apply into a combination that no longer exists.',

    'offering_references_deleted_level_title' => 'Online applications offer a level that has been deleted',
    'offering_references_deleted_level_body' => 'The department level behind this entry in the online application catalogue has been deleted, but the catalogue still offers it to applicants.',

    'offering_references_deleted_course_title' => 'Online applications offer a course that has been deleted',
    'offering_references_deleted_course_body' => 'The department course behind this entry in the online application catalogue has been deleted, but the catalogue still offers it to applicants.',

    'intake_period_missing_offer_letters_title' => ':intake has no offer letters',
    'intake_period_missing_offer_letters_body' => 'Each intake needs its own offer letters before students can be accepted. Open the intake and add or copy the working set (HEXCO, ABMA, Block Release, SDP).',

    // Notification
    'notification_title' => 'New setup issue: :check',
    'notification_title_many' => ':count new setup issues',
    'notification_body' => ':title',
    'notification_body_many' => 'The nightly check found :count new setup issue(s) that need attention.',
];
