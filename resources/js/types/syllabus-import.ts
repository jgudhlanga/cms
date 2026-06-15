export type SyllabusImportPreviewAction = 'create' | 'update' | 'skip' | 'fail';

export interface SyllabusImportRowCorrection {
    level?: string;
    courseTitle?: string;
    courseCode?: string;
    semester?: string;
    moduleTitle?: string;
    moduleCode?: string;
}

export interface SyllabusImportPreviewLookups {
    levels: string[];
    courses: string[];
    levelCourses: string[];
    semesters: string[];
}

export interface SyllabusImportPreviewRow {
    rowNumber: number;
    level: string;
    courseTitle: string;
    courseCode: string;
    semester: string;
    moduleTitle: string;
    moduleCode: string;
    moduleCodeOccurrencesInFile: number;
    moduleCodeRepeatedInFile: boolean;
    syllabusExists: boolean;
    moduleExists: boolean;
    syllabusAction: SyllabusImportPreviewAction;
    moduleAction: SyllabusImportPreviewAction;
    syllabusErrors: string[];
    moduleErrors: string[];
}

export interface SyllabusImportPreviewSummary {
    total: number;
    syllabusCreates: number;
    syllabusUpdates: number;
    syllabusSkips: number;
    syllabusFails: number;
    moduleCreates: number;
    moduleUpdates: number;
    moduleSkips: number;
    moduleFails: number;
    failed: number;
}

export interface SyllabusImportPreviewFileStats {
    totalRows: number;
    uniqueCourseCodes: number;
    uniqueModuleCodes: number;
    uniqueModuleRecords: number;
    duplicateModuleCodeGroups: number;
    extraRowsFromDuplicateModuleCodes: number;
    moduleRows: number;
    moduleSkipRows: number;
}

export interface SyllabusImportPreview {
    previewToken: string;
    fileName: string;
    summary: SyllabusImportPreviewSummary;
    fileStats: SyllabusImportPreviewFileStats;
    lookups: SyllabusImportPreviewLookups;
    rows: SyllabusImportPreviewRow[];
}

export interface SyllabusImportResult {
    ingestRunId: number;
    importLogId: number;
    rowsTotal: number;
    rowsSucceeded: number;
    rowsFailed: number;
    rowsSkipped: number;
}
