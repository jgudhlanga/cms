import { trans } from 'laravel-vue-i18n';

export function hostelApplicationBlockerMessage(key: string): string {
    const messages: Record<string, string> = {
        no_running_semester: trans('hms.no_running_semester'),
        no_hostel_capacity: trans('hms.no_hostel_capacity'),
        unknown_gender_for_hostel: trans('hms.unknown_gender_for_hostel'),
        calendar_year_missing: trans('hms.calendar_year_missing'),
        pending_application_exists: trans('hms.student_pending_application_exists'),
    };

    return messages[key] ?? key;
}
