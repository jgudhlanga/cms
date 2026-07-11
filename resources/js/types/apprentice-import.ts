import type { FaultyStudentIdConflict } from '@/types/faulty-student-ids';

export type ApprenticeImportPreviewStatus = 'found' | 'not_found' | 'invalid';

export type ApprenticeClassListStatus =
    | 'provisional'
    | 'verified'
    | 'waiting'
    | 'final'
    | 'failed';

export type ApprenticeIdRectificationStatus =
    | 'ready_to_fix'
    | 'duplicate_merge'
    | 'manual_correction';

export type ApprenticeImportMatchedBy = 'id_number' | 'student_number';

export interface ApprenticeImportPreviewRow {
    rowNumber: number;
    idNumber: string | null;
    studentNumber: string | null;
    apprenticeNumber: string | null;
    employer: string | null;
    status: ApprenticeImportPreviewStatus;
    studentId: number | null;
    studentName: string | null;
    matchedBy: ApprenticeImportMatchedBy | null;
    storedIdNumber: string | null;
    errors: string[];
    departmentCode: string | null;
    level: string | null;
    course: string | null;
    classListStatus: ApprenticeClassListStatus | null;
    studentApplicationId: number | null;
    idNumberValid: boolean;
    suggestedIdNumber: string | null;
    idRectificationStatus: ApprenticeIdRectificationStatus | null;
    idConflict: FaultyStudentIdConflict | null;
    isAlreadyApprentice: boolean;
    isSelectable: boolean;
    skipReasons: string[];
}

export interface ApprenticeImportPreviewSummary {
    total: number;
    found: number;
    notFound: number;
    invalid: number;
    alreadyApprentice: number;
    invalidId: number;
    selectable: number;
}

export interface ApprenticeImportPreview {
    summary: ApprenticeImportPreviewSummary;
    rows: ApprenticeImportPreviewRow[];
}

export interface ApprenticeImportRefreshRowResponse {
    row: ApprenticeImportPreviewRow;
}

export interface ApprenticeImportProcessRowPayload {
    rowNumber: number;
    studentApplicationId: number;
    apprenticeNumber?: string | null;
    employer?: string | null;
}

export interface ApprenticeImportProcessResultRow {
    rowNumber: number;
    status: 'moved' | 'skipped';
    reason?: string;
}

export interface ApprenticeImportProcessResult {
    summary: {
        requested: number;
        moved: number;
        skipped: number;
    };
    rows: ApprenticeImportProcessResultRow[];
}
