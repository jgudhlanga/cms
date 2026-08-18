export type StudentIdCardImportPreviewStatus = 'ready' | 'invalid';

export type StudentIdCardImportMatchedBy = 'student_number' | 'id_number' | 'passport_number';

export type StudentIdCardImportFilter = 'all' | 'ready' | 'errors';

export interface StudentIdCardImportPreviewRow {
    rowNumber: number;
    studentNumber: string | null;
    idNumber: string | null;
    passportNumber: string | null;
    status: StudentIdCardImportPreviewStatus;
    studentId: number | null;
    studentName: string | null;
    matchedBy: StudentIdCardImportMatchedBy | null;
    storedStudentNumber: string | null;
    storedIdNumber: string | null;
    storedPassportNumber: string | null;
    identityType: string | null;
    hasPhoto: boolean;
    photoThumbUrl: string | null;
    existingRequestId: number | null;
    existingRequestStatus: string | null;
    errors: string[];
    warnings: string[];
    skipReasons: string[];
    isSelectable: boolean;
}

export interface StudentIdCardImportPreview {
    summary: {
        total: number;
        ready: number;
        errors: number;
        selectable: number;
    };
    rows: StudentIdCardImportPreviewRow[];
}

export interface StudentIdCardImportProcessResult {
    summary: {
        requested: number;
        imported: number;
        skipped: number;
    };
    rows: Array<{
        rowNumber: number;
        status: 'imported' | 'skipped';
        reason?: string;
    }>;
}
