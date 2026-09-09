export type DepartmentReconciliationCounts = {
    enrolledThisYear: number;
    enrolledThisPeriod: number;
    calendarYear: number;
};

export type EnrolmentVsClassListStatus =
    | 'matched'
    | 'elevate'
    | 'wrong_place'
    | 'in_admissions'
    | 'not_found'
    | 'invalid';

export interface EnrolmentVsClassListPreviewRow {
    rowNumber: number;
    studentNumber: string | null;
    fileLevel: string | null;
    fileCourse: string | null;
    studentId: number | null;
    studentName: string | null;
    studentApplicationId: number | null;
    workflowStep: string | null;
    classListType: string | null;
    systemDepartment: string | null;
    systemLevel: string | null;
    systemCourse: string | null;
    highlight: string | null;
    errors: string[];
    skipReasons: string[];
    isSelectable: boolean;
    status: EnrolmentVsClassListStatus;
}

export interface EnrolmentVsClassListExtraRow {
    studentEnrolmentId: number;
    studentId: number;
    studentNumber: string;
    studentName: string | null;
    level: string | null;
    course: string | null;
    highlight: string | null;
}

export interface EnrolmentVsClassListPreviewSummary {
    total: number;
    matched: number;
    elevate: number;
    wrongPlace: number;
    inAdmissions: number;
    notFound: number;
    invalid: number;
    selectable: number;
    extras: number;
}

export interface EnrolmentVsClassListPreview {
    summary: EnrolmentVsClassListPreviewSummary;
    rows: EnrolmentVsClassListPreviewRow[];
    extras: EnrolmentVsClassListExtraRow[];
}

export interface EnrolmentVsClassListProcessResult {
    summary: {
        requested: number;
        moved: number;
        skipped: number;
    };
    rows: Array<{
        rowNumber: number;
        status: 'moved' | 'skipped';
        reason?: string;
    }>;
}

export type SemesterReconciliationStatus =
    | 'matched'
    | 'mismatch'
    | 'not_enrolled'
    | 'wrong_place'
    | 'invalid';

export interface SemesterReconciliationPreviewRow {
    rowNumber: number;
    studentNumber: string | null;
    fileLevel: string | null;
    fileCourse: string | null;
    filePhase: string | null;
    studentId: number | null;
    studentName: string | null;
    studentEnrolmentId: number | null;
    programmeSemesterId: number | null;
    systemLevel: string | null;
    systemCourse: string | null;
    systemPhase: string | null;
    highlight: string | null;
    errors: string[];
    skipReasons: string[];
    isSelectable: boolean;
    status: SemesterReconciliationStatus;
}

export interface SemesterReconciliationExtraRow {
    studentEnrolmentId: number;
    studentId: number;
    studentNumber: string;
    studentName: string | null;
    level: string | null;
    course: string | null;
    systemPhase: string | null;
    highlight: string | null;
}

export interface SemesterReconciliationPreviewSummary {
    total: number;
    matched: number;
    mismatch: number;
    notEnrolled: number;
    wrongPlace: number;
    invalid: number;
    selectable: number;
    extras: number;
}

export interface SemesterReconciliationPreview {
    summary: SemesterReconciliationPreviewSummary;
    rows: SemesterReconciliationPreviewRow[];
    extras: SemesterReconciliationExtraRow[];
}

export interface SemesterReconciliationProcessResult {
    summary: {
        requested: number;
        moved: number;
        skipped: number;
    };
    rows: Array<{
        rowNumber: number;
        status: 'moved' | 'skipped';
        reason?: string;
    }>;
}
