export type FaultyApplicationReason =
    | 'missing_level'
    | 'missing_department'
    | 'missing_course'
    | 'missing_mode_of_study'
    | 'missing_intake';

export interface FaultyApplicationAttributes {
    studentId: number | null;
    name: string | null;
    email: string | null;
    studentNumber: string | null;
    trackingNumber: string | null;
    department: string | null;
    level: string | null;
    course: string | null;
    modeOfStudy: string | null;
    intakePeriod: string | null;
    applicationStatus: string | null;
    reasons: FaultyApplicationReason[];
}

export interface FaultyApplication {
    type: string;
    id: number;
    attributes: FaultyApplicationAttributes;
}

export interface FaultyApplicationsFiltersState {
    search?: string;
}
