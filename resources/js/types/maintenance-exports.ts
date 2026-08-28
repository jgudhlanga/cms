export interface MaintenanceExportCounts {
    studentEnrolments: number;
    applications: number;
    faultyStudentIds: number;
    faultyApplications: number;
}

export interface MaintenanceExportBreakdown {
    name: string;
    count: number;
}

export interface StudentEnrolmentExportFiltersState {
    search?: string | null;
    department?: number[] | null;
    level?: number[] | null;
    course?: number[] | null;
    mode_of_study?: number[] | null;
    gender?: 'male' | 'female' | null;
    student_type?: 'direct' | 'apprentice' | null;
    sponsored?: 'sponsored' | 'not_sponsored' | null;
    disability?: 'yes' | 'no' | null;
    intake_year?: string | null;
    calendar_year?: string | null;
    semester_id?: number | null;
    calendar_type?: string | null;
}

export interface ApplicationExportFiltersState {
    search?: string | null;
    department?: number[] | null;
    level?: number[] | null;
    course?: number[] | null;
    mode_of_study?: number[] | null;
    gender?: 'male' | 'female' | null;
    student_type?: 'direct' | 'apprentice' | null;
    sponsored?: 'sponsored' | 'not_sponsored' | null;
    disability?: 'yes' | 'no' | null;
    intake_year?: string | null;
    intake_period_id?: number | null;
    applied_from?: string | null;
    applied_to?: string | null;
}

export interface StudentEnrolmentExportStats {
    total: number;
    byLevel: MaintenanceExportBreakdown[];
    byGender: MaintenanceExportBreakdown[];
    byModeOfStudy: MaintenanceExportBreakdown[];
}

export interface ApplicationExportStats {
    total: number;
    byWorkflowStep: MaintenanceExportBreakdown[];
    byLevel: MaintenanceExportBreakdown[];
}

export interface StudentEnrolmentExportPreviewRow {
    type: string;
    id: number;
    attributes: {
        studentId: number | null;
        name: string | null;
        studentNumber: string | null;
        gender: string | null;
        department: string | null;
        level: string | null;
        course: string | null;
        modeOfStudy: string | null;
        semester: string | null;
        calendarYear: string | null;
        calendarType: string | null;
    };
}

export interface ApplicationExportPreviewRow {
    type: string;
    id: number;
    attributes: {
        studentId: number | null;
        name: string | null;
        studentNumber: string | null;
        department: string | null;
        level: string | null;
        course: string | null;
        modeOfStudy: string | null;
        intakePeriod: string | null;
        applicationStatus: string | null;
        appliedAt: string | null;
    };
}

export interface MaintenanceSemesterOption {
    id: number;
    name: string;
    slug: string;
}

export interface MaintenanceCalendarTypeOption {
    value: string;
    label: string;
}
