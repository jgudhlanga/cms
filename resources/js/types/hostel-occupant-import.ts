export type HostelOccupantImportPreviewStatus = 'ready' | 'invalid';

export type HostelOccupantImportPaymentSource =
    | 'ledger'
    | 'bank'
    | 'sponsored'
    | 'apprentice'
    | 'assumed_paid';

export type HostelOccupantImportMatchedBy = 'student_number' | 'id_number' | 'passport_number';

export type HostelOccupantImportFilter = 'all' | 'ready' | 'assumed_paid' | 'errors';

export interface HostelOccupantImportPreviewRow {
    rowNumber: number;
    studentNumber: string | null;
    idNumber: string | null;
    passportNumber: string | null;
    disability: string | null;
    hostel: string | null;
    floor: string | null;
    room: string | null;
    section: string | null;
    status: HostelOccupantImportPreviewStatus;
    studentId: number | null;
    studentName: string | null;
    matchedBy: HostelOccupantImportMatchedBy | null;
    storedStudentNumber: string | null;
    storedIdNumber: string | null;
    storedPassportNumber: string | null;
    hostelRoomId: number | null;
    hostelRoomSectionId: number | null;
    resolvedFloor: number | null;
    resolvedRoom: string | null;
    resolvedSection: string | null;
    nextOfKinName: string | null;
    nextOfKinContact: string | null;
    paymentSource: HostelOccupantImportPaymentSource | null;
    isSponsored: boolean;
    isApprentice: boolean;
    errors: string[];
    warnings: string[];
    skipReasons: string[];
    isSelectable: boolean;
}

export interface HostelOccupantImportPreviewSummary {
    total: number;
    ready: number;
    assumedPaid: number;
    errors: number;
    selectable: number;
}

export interface HostelOccupantImportPreview {
    summary: HostelOccupantImportPreviewSummary;
    rows: HostelOccupantImportPreviewRow[];
}

export interface HostelOccupantImportProcessRowPayload {
    rowNumber: number;
    studentId: number;
    disability?: string | null;
    hostelRoomId: number;
    hostelRoomSectionId: number;
}

export interface HostelOccupantImportProcessResultRow {
    rowNumber: number;
    status: 'imported' | 'skipped';
    reason?: string;
}

export interface HostelOccupantImportProcessResult {
    summary: {
        requested: number;
        imported: number;
        skipped: number;
    };
    rows: HostelOccupantImportProcessResultRow[];
}
