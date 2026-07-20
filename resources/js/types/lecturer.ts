export interface LecturerQuickAction {
    key: string;
    label: string;
    url: string | null;
    enabled: boolean;
}

export interface LecturerPriorityAlert {
    severity: string;
    message: string;
    updatedAt: string | null;
    kind?: string | null;
    daysRemaining?: number | null;
    endDate?: string | null;
    assessmentTypeName?: string | null;
}

export interface LecturerStudentRow {
    studentEnrolmentId: number;
    studentId: number;
    studentName: string;
    averageMark?: number | null;
    modulesCount?: number;
    failCount?: number;
}

export interface LecturerMissingCourseWork {
    academicCalendarClassId: number;
    className: string;
    moduleId: number;
    moduleName: string;
    moduleCode: string;
    incompleteCount: number;
    outstandingCount: number;
}

export interface LecturerModuleSummary {
    moduleId: number;
    moduleName: string;
    moduleCode: string;
    classesCount: number;
    studentsCount: number;
    passRate: number | null;
    averageMark: number | null;
    incompleteCount: number;
}

export interface LecturerDashboardSummary {
    passRate: number | null;
    averageMark: number | null;
    modulesCount: number;
    classesCount: number;
    markCompletionRate: number | null;
    atRiskStudentCount: number | null;
    missingCourseWorkCount: number;
}

export interface LecturerDashboard {
    summary: LecturerDashboardSummary;
    attendance: null;
    topPerformingStudents: LecturerStudentRow[];
    lowPerformingStudents: LecturerStudentRow[];
    riskyStudents: LecturerStudentRow[];
    missingCourseWork: LecturerMissingCourseWork[];
    priorityAlerts: LecturerPriorityAlert[];
    modules: LecturerModuleSummary[];
    quickActions: LecturerQuickAction[];
}

export interface LecturerModuleClass {
    id: number;
    name: string;
}

export interface TeachingClassAssessmentWindow {
    assessmentTypeName: string;
    startDate: string | null;
    endDate: string | null;
    isOpen: boolean;
}

export interface TeachingClassStats {
    assignedModuleCount: number;
    missingCourseWorkCount: number;
    passRate: number | null;
    averageMark: number | null;
}

export interface TeachingClassCard {
    academicCalendarClassId: number | null;
    name: string;
    studentCount: number;
    genderCounts: {
        male: number;
        female: number;
        unknown: number;
    };
    tutor?: {
        id: number;
        name: string;
    } | null;
    isTutor?: boolean;
    departmentName?: string;
    courseName?: string;
    levelName?: string;
    modeOfStudyName?: string;
    calendarYear?: string;
    moduleCodes: string[];
    assignedModuleCodes: string[];
    assessmentWindows: TeachingClassAssessmentWindow[];
    stats: TeachingClassStats;
}

export interface TeachingClassesSummary {
    classCount: number;
    studentCount: number;
    assignedModuleCount: number;
    openAssessmentWindowCount: number;
    missingCourseWorkCount: number;
}

export interface LecturerModuleRow {
    id: number;
    title: string;
    code: string;
    departmentName: string;
    classes: LecturerModuleClass[];
    classesCount: number;
}
