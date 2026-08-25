export type AssessmentCalendarWindowSeverity = 'info' | 'warning' | 'critical';

export type AssessmentCalendarWindow = {
    assessmentCalendarId: number;
    assessmentTypeId: number;
    assessmentTypeName: string;
    startDate: string | null;
    endDate: string | null;
    firstNotificationDate: string | null;
    secondNotificationDate: string | null;
    dueNotificationDate: string | null;
    firstNotificationDaysBefore: number;
    secondNotificationDaysBefore: number;
    dueNotificationDaysBefore: number;
    daysRemaining: number | null;
    isOpen: boolean;
    isInNotificationWindow: boolean;
    missingCount: number;
    severity: AssessmentCalendarWindowSeverity;
};

export type MissingMarksReportFilterOption = {
    id: number;
    label: string;
};

export type MissingMarksReportFilters = {
    academicCalendarId: number | null;
    assessmentTypeId: number | null;
    departmentId: number | null;
    lecturerStaffId: number | null;
};

export type MissingMarksReportRow = {
    assessmentCalendarId: number;
    assessmentTypeName: string;
    className: string;
    moduleName: string;
    moduleCode: string;
    lecturerNames: string;
    incompleteCount: number;
    dueDate: string | null;
    lastTier: string | null;
    lastTierLabel: string | null;
    escalated: boolean;
};
