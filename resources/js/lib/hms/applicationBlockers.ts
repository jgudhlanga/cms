import { trans } from 'laravel-vue-i18n';

export function hostelApplicationBlockerMessage(key: string): string {
    const messages: Record<string, string> = {
        applications_closed: trans('hms.applications_closed'),
        application_dates_not_configured: trans('hms.application_dates_not_configured'),
        applications_not_yet_open: trans('hms.applications_not_yet_open'),
        applications_period_ended: trans('hms.applications_period_ended'),
        no_running_semester: trans('hms.no_running_semester'),
        no_hostel_capacity: trans('hms.no_hostel_capacity'),
        unknown_gender_for_hostel: trans('hms.unknown_gender_for_hostel'),
        calendar_year_missing: trans('hms.calendar_year_missing'),
        pending_application_exists: trans('hms.student_pending_application_exists'),
        student_already_allocated: trans('hms.student_already_allocated'),
    };

    return messages[key] ?? key;
}
