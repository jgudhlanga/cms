export interface FaultyStudentIdNumberAttributes {
    name: string | null;
    email: string | null;
    studentNumber: string | null;
    idNumber: string;
    suggestedIdNumber: string | null;
}

export interface FaultyStudentIdNumber {
    type: string;
    id: number;
    attributes: FaultyStudentIdNumberAttributes;
}

export interface FaultyStudentIdsFiltersState {
    search?: string;
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
}

export interface StudentAccountMergePreview {
    proposedIdNumber: string;
    source: StudentAccountMergeSummary;
    target: StudentAccountMergeSummary;
}
