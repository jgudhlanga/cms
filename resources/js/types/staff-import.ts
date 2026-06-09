export interface StaffImportPreviewRow {
    rowNumber: number;
    employeeNumber: string | null;
    fullName: string | null;
    email: string | null;
    department: string | null;
    action: 'create' | 'update' | 'skip_empty' | 'fail';
    errors: Record<string, string[]> | null;
}

export interface StaffImportPreview {
    previewToken: string;
    fileName: string;
    summary: {
        total: number;
        succeeded: number;
        failed: number;
        skipped: number;
        creates: number;
        updates: number;
    };
    rows: StaffImportPreviewRow[];
}

export interface StaffImportResult {
    ingestRunId: number;
    importLogId: number;
    rowsTotal: number;
    rowsSucceeded: number;
    rowsFailed: number;
    rowsSkipped: number;
}
