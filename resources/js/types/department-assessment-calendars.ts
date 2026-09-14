export type AssessmentWindowStatus = 'not_configured' | 'not_open' | 'open' | 'extended' | 'closed';

export interface DepartmentAssessmentCalendarWindow {
    id: number;
    startDate: string;
    endDate: string;
    firstNotificationDaysBefore: number | null;
    secondNotificationDaysBefore: number | null;
    dueNotificationDaysBefore: number | null;
    notes: string | null;
    trashed: boolean;
    started: boolean;
    closed: boolean;
}

export interface DepartmentAssessmentCalendarRow {
    globalCalendarId: number;
    assessmentTypeName: string;
    modesOfStudy: string[];
    academicCalendarLabel: string;
    globalStartDate: string;
    globalEndDate: string;
    globalClosed: boolean;
    defaultNotificationDays: { first: number; second: number; due: number };
    departmentCalendar: DepartmentAssessmentCalendarWindow | null;
    effectiveStartDate: string;
    effectiveEndDate: string;
    status: AssessmentWindowStatus;
    statusLabel: string;
}

export interface DepartmentAssessmentCalendarPermissions {
    create: boolean;
    update: boolean;
    delete: boolean;
    restore: boolean;
}
