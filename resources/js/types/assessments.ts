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
    calendarYear: number;
    departmentId: number | null;
    levelId: number | null;
    courseId: number | null;
    moduleId: number | null;
    lecturerStaffId: number | null;
    assessmentTypeId: number | null;
};

export type MissingMarksReportFilterOptions = {
    departments: MissingMarksReportFilterOption[];
    levels: MissingMarksReportFilterOption[];
    courses: MissingMarksReportFilterOption[];
    modules: MissingMarksReportFilterOption[];
    lecturers: MissingMarksReportFilterOption[];
    assessmentTypes: MissingMarksReportFilterOption[];
};

export type MissingMarksEscalationTarget = {
    assessmentCalendarId: number;
    assessmentTypeName: string;
    dueDate: string | null;
};

export type MissingMarksReportRow = {
    assessmentCalendarId: number;
    assessmentTypeId: number;
    assessmentTypeName: string;
    departmentName: string;
    levelName: string;
    courseName: string;
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
