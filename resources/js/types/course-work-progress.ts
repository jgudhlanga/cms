import type { CourseWorkWindowSummary } from '@/types/course-work-extensions';

export interface CourseWorkProgressTotals {
    expected: number;
    captured: number;
    missing: number;
    percent?: number | null;
}

export interface CourseWorkProgressModule {
    id: number;
    code: string;
    title: string;
    captureMarkOnly: boolean;
    lecturers: string[];
    expected: number;
    captured: number;
    missing: number;
    byAssessment: Array<{ assessmentTypeId: number | null; expected: number; captured: number; missing: number }>;
}

export interface CourseWorkProgressClass {
    id: number;
    name: string;
    studentCount: number;
    modules: CourseWorkProgressModule[];
}

export interface CourseWorkProgress {
    classConfigId: number;
    institutionDepartmentId: number;
    academicCalendarId: number | null;
    calendarYear: string;
    programme: string;
    departmentName: string;
    lecturerInCharge: { staffId: number; userId: number | null; name: string } | null;
    assessments: CourseWorkWindowSummary[];
    totals: CourseWorkProgressTotals;
    classes: CourseWorkProgressClass[];
    lastReport: {
        id: number;
        submittedAt: string | null;
        submitterName: string;
        acknowledgedAt: string | null;
        acknowledgerName: string;
        hodComment: string | null;
    } | null;
}

export interface CourseWorkProgressReportRow {
    id: number;
    programme?: string;
    departmentName?: string;
    submitterName: string;
    submittedAt: string | null;
    notes: string | null;
    totals: CourseWorkProgressTotals | null;
    acknowledgedAt: string | null;
    acknowledgerName: string;
    hodComment: string | null;
    progressUrl?: string;
    can?: { acknowledge: boolean };
}
