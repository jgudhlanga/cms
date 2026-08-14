export type SponsoredStudentImportPreviewStatus = 'found' | 'not_found' | 'invalid';

export type SponsoredStudentClassListStatus =
    | 'provisional'
    | 'verified'
    | 'waiting'
    | 'final'
    | 'failed';

export type SponsoredStudentImportAction = 'create' | 'update';

export interface SponsoredStudentImportPreviewRow {
    rowNumber: number;
    studentNumber: string | null;
    sponsor: string | null;
    status: SponsoredStudentImportPreviewStatus;
    studentId: number | null;
    studentName: string | null;
    matchedBy: 'student_number' | null;
    storedIdNumber: string | null;
    passportNumber: string | null;
    identityNumber: string | null;
    errors: string[];
    departmentCode: string | null;
    level: string | null;
    course: string | null;
    classListStatus: SponsoredStudentClassListStatus | null;
    studentApplicationId: number | null;
    idNumberValid: boolean;
    isAlreadySponsored: boolean;
    existingSponsor: string | null;
    action: SponsoredStudentImportAction | null;
    isSelectable: boolean;
    skipReasons: string[];
}

export interface SponsoredStudentImportPreviewSummary {
    total: number;
    found: number;
    notFound: number;
    invalid: number;
    alreadySponsored: number;
    invalidId: number;
    selectable: number;
}

export interface SponsoredStudentImportPreview {
    summary: SponsoredStudentImportPreviewSummary;
    rows: SponsoredStudentImportPreviewRow[];
}

export interface SponsoredStudentImportProcessRowPayload {
    rowNumber: number;
    studentApplicationId: number;
    sponsor?: string | null;
}

export interface SponsoredStudentImportProcessResultRow {
    rowNumber: number;
    status: 'moved' | 'skipped';
    reason?: string;
}

export interface SponsoredStudentImportProcessResult {
    summary: {
        requested: number;
        moved: number;
        skipped: number;
    };
    rows: SponsoredStudentImportProcessResultRow[];
}
