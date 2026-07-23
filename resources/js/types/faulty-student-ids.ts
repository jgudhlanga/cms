export type FaultyStudentRectificationStatus = 'duplicate_merge' | 'ready_to_fix' | 'manual_correction';

export interface FaultyStudentIdConflict {
    conflictingStudentId: number;
    conflictingStudentName: string | null;
    conflictingStudentNumber: string | null;
    conflictingPhoneNumber: string | null;
    idNumber: string;
    mergePreviewUrl: string;
}

export interface FaultyStudentIdNumberAttributes {
    name: string | null;
    email: string | null;
    phoneNumber: string | null;
    studentNumber: string | null;
    idNumber: string;
    suggestedIdNumber: string | null;
    proposedIdNumber: string | null;
    rectificationStatus: FaultyStudentRectificationStatus;
    conflict: FaultyStudentIdConflict | null;
}

export interface FaultyStudentIdNumber {
    type: string;
    id: number;
    attributes: FaultyStudentIdNumberAttributes;
}

export interface FaultyStudentIdsFiltersState {
    search?: string;
}

export interface FixStudentIdConflictResponse {
    conflictingStudentId?: number;
    conflicting_student_id?: number;
    idNumber?: string;
    id_number?: string;
    mergeUrl?: string;
    merge_url?: string;
}

export interface StudentAccountMergeApplication {
    id: number;
    departmentCode: string | null;
    level: string | null;
    course: string | null;
    intakePeriod: string | null;
    modeOfStudy: string | null;
    applicationStatus: string | null;
    classListType: string | null;
    canReject: boolean;
}

export interface StudentAccountMergeSummary {
    studentId: number;
    userId: number;
    name: string | null;
    email: string | null;
    phoneNumber: string | null;
    studentNumber: string | null;
    idNumber: string | null;
    isFaultySource: boolean;
    programmesCount: number;
    enrolmentsCount: number;
    paidReceiptsCount: number;
    contactsCount: number;
    addressesCount: number;
    academicResultsCount: number;
    hostelApplicationsCount: number;
    applications: StudentAccountMergeApplication[];
}

export interface StudentAccountMergePreview {
    proposedIdNumber: string;
    source: StudentAccountMergeSummary;
    target: StudentAccountMergeSummary;
}

export interface FaultyStudentIdsBulkFixResult {
    fixed_ids: number[];
    failed: Array<{
        id: number;
        message: string;
    }>;
}

export interface FaultyStudentMergeResult {
    studentId: number;
    userId: number;
    name: string | null;
    email: string | null;
    phoneNumber: string | null;
    studentNumber: string | null;
    idNumber: string | null;
    programmesCount: number;
    enrolmentsCount: number;
}
