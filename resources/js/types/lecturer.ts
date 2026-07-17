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

export interface LecturerClassRow {
    id: number;
    name: string;
    description: string | null;
    departmentName: string;
    courseName: string;
    levelName: string;
    modeOfStudyName: string;
    calendarYear: string;
    modulesCount: number;
    isTutor: boolean;
}

export interface LecturerModuleClass {
    id: number;
    name: string;
}

export interface LecturerModuleRow {
    id: number;
    title: string;
    code: string;
    departmentName: string;
    classes: LecturerModuleClass[];
    classesCount: number;
}
