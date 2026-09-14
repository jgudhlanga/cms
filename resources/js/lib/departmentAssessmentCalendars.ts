import type {
    AssessmentWindowStatus,
    DepartmentAssessmentCalendarPermissions,
    DepartmentAssessmentCalendarRow,
} from '@/types/department-assessment-calendars';

export type DepartmentCalendarAction = 'set' | 'edit' | 'revert' | 'restore';

/**
 * Which actions a row offers. Mirrors the server rules: nothing once the college window has closed
 * (lecturers request extensions instead), and a closed department window can no longer be edited or
 * removed because either would reopen capture.
 */
export const departmentCalendarActions = (
    row: DepartmentAssessmentCalendarRow,
    can: DepartmentAssessmentCalendarPermissions,
): DepartmentCalendarAction[] => {
    if (row.globalClosed) {
        return [];
    }

    const departmentCalendar = row.departmentCalendar;
    const active = departmentCalendar !== null && !departmentCalendar.trashed;
    const actions: DepartmentCalendarAction[] = [];

    if (!active) {
        if (departmentCalendar?.trashed && can.restore) {
            actions.push('restore');
        }

        if (can.create) {
            actions.push('set');
        }

        return actions;
    }

    if (departmentCalendar.closed) {
        return actions;
    }

    if (can.update) {
        actions.push('edit');
    }

    if (can.delete) {
        actions.push('revert');
    }

    return actions;
};

/**
 * Formats a Y-m-d date as e.g. "12 Sep 2026" without shifting it through UTC.
 */
export const formatIsoDate = (isoDate?: string | null): string => {
    if (!isoDate) {
        return '';
    }

    const [year, month, day] = isoDate.slice(0, 10).split('-').map(Number);

    if (!year || !month || !day) {
        return isoDate;
    }

    return new Date(year, month - 1, day).toLocaleDateString('en-GB', {
        day: '2-digit',
        month: 'short',
        year: 'numeric',
    });
};

export const formatIsoDateRange = (startDate?: string | null, endDate?: string | null): string =>
    [formatIsoDate(startDate), formatIsoDate(endDate)].filter(Boolean).join(' – ');

/**
 * Status badges always carry their text label; colour only reinforces it.
 */
export const windowStatusBadgeClass = (status: AssessmentWindowStatus): string => {
    switch (status) {
        case 'open':
            return 'bg-emerald-100 text-emerald-800 dark:bg-emerald-900/40 dark:text-emerald-200';
        case 'extended':
            return 'bg-sky-100 text-sky-800 dark:bg-sky-900/40 dark:text-sky-200';
        case 'not_open':
            return 'bg-amber-100 text-amber-800 dark:bg-amber-900/40 dark:text-amber-200';
        case 'closed':
            return 'bg-rose-100 text-rose-800 dark:bg-rose-900/40 dark:text-rose-200';
        default:
            return 'bg-muted text-muted-foreground';
    }
};
