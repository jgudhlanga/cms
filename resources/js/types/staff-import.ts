export interface StaffImportLookupOption {
    value: number;
    label: string;
    roleGroup?: string;
}

export type StaffImportLookupType =
    | 'title'
    | 'gender'
    | 'marital_status'
    | 'employment_type'
    | 'department'
    | 'role';

export type StaffImportFieldKey =
    | 'title'
    | 'gender'
    | 'maritalStatus'
    | 'employmentType'
    | 'department'
    | 'roles';

export interface StaffImportLookupField {
    raw: string;
    resolvedId: number | null;
    resolvedLabel: string | null;
    matchType: 'exact' | 'fuzzy' | 'manual' | 'created' | null;
    needsReview: boolean;
}

export interface StaffImportRowFields {
    title: StaffImportLookupField;
    gender: StaffImportLookupField;
    maritalStatus: StaffImportLookupField;
    employmentType: StaffImportLookupField;
    department: StaffImportLookupField;
    roles: StaffImportLookupField[];
}

export interface StaffImportRowCorrection {
    titleId?: number;
    genderId?: number;
    maritalStatusId?: number;
    employmentTypeId?: number;
    institutionDepartmentId?: number;
    roleIds?: number[];
    email?: string;
    phoneNumber?: string;
    dateOfBirth?: string;
}

export interface StaffImportPreviewRow {
    rowNumber: number;
    employeeNumber: string | null;
    fullName: string | null;
    email: string | null;
    phoneNumber: string | null;
    dateOfBirth: string | null;
    department: string | null;
    action: 'create' | 'update' | 'skip_empty' | 'fail';
    errors: Record<string, string[]> | null;
    fields: StaffImportRowFields;
    needsReview: boolean;
}

export interface StaffImportPreviewLookups {
    titles: StaffImportLookupOption[];
    genders: StaffImportLookupOption[];
    maritalStatuses: StaffImportLookupOption[];
    employmentTypes: StaffImportLookupOption[];
    departments: StaffImportLookupOption[];
    roles: StaffImportLookupOption[];
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
    lookups: StaffImportPreviewLookups;
    rows: StaffImportPreviewRow[];
}

export interface StaffImportFailedRow {
    rowNumber: number;
    employeeNumber: string | null;
    fullName: string | null;
    email: string | null;
    errors: string[];
}

export interface StaffImportResult {
    ingestRunId: number;
    importLogId: number;
    rowsTotal: number;
    rowsSucceeded: number;
    rowsFailed: number;
    rowsSkipped: number;
    failedRows: StaffImportFailedRow[];
}
