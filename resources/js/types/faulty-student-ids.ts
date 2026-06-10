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
