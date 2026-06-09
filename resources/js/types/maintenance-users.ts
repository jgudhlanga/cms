export interface MaintenanceUserRole {
    id: number;
    name: string;
}

export interface NonEnrolledStudentUserAttributes {
    name: string;
    email: string;
    phoneNumber: string | null;
    lastLoginAt: string | null;
    createdAt: string;
    hasStudentProfile: boolean;
    studentId: number | null;
    studentNumber: string | null;
    applicationStatusSummary: string;
    roles: MaintenanceUserRole[];
}

export interface MaintenanceUserBulkPurgeResult {
    purged: number[];
    skipped: number[];
}

export interface NonEnrolledStudentUser {
    type: string;
    id: number;
    attributes: NonEnrolledStudentUserAttributes;
}

export interface MaintenanceUsersFiltersState {
    search?: string;
}
