export type CourseWorkExtensionStatus = 'pending' | 'approved' | 'rejected' | 'revoked' | 'cancelled';

export interface CourseWorkWindowSummary {
    assessmentTypeId: number | null;
    assessmentTypeName: string;
    status: string;
    statusLabel?: string;
    captureAllowed?: boolean;
    startDate?: string | null;
    endDate?: string | null;
    extendedUntil?: string | null;
    message: string;
}

export interface CourseWorkExtensionRequestTarget {
    classId: number;
    className: string;
    moduleId: number;
    moduleName: string;
    maxDays: number;
    closedWindows: CourseWorkWindowSummary[];
}

export interface CourseWorkExtensionRow {
    id: number;
    className: string;
    moduleName: string;
    assessmentName: string;
    departmentName: string;
    requesterName: string;
    reason: string;
    requestedUntil: string | null;
    approvedUntil: string | null;
    globalEndDate: string | null;
    status: CourseWorkExtensionStatus;
    statusLabel: string;
    decisionNote: string | null;
    deciderName: string;
    createdAt: string | null;
    decidedAt: string | null;
    can: {
        approve: boolean;
        reject: boolean;
        revoke: boolean;
        cancel: boolean;
        approveBeyondGlobal: boolean;
    };
}

export type CourseWorkExtensionDecisionMode = 'approve' | 'reject' | 'revoke';

export interface CourseWorkExtensionDecisionTarget {
    mode: CourseWorkExtensionDecisionMode;
    extension: CourseWorkExtensionRow;
}
