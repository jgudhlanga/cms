export type CourseWorkAssessmentType = {
    id: number;
    name: string;
    description: string | null;
    weightPercent: number | null;
};

export type CourseWorkAggregationComponent = {
    assessmentTypeId: number;
    assessmentTypeName: string;
    rawMark: number | null;
    weightPercent: number;
    weightedMark: number | null;
};

export type CourseWorkAggregation = {
    components: CourseWorkAggregationComponent[];
    courseWorkTotal60: number | null;
    isComplete: boolean;
    remark: string | null;
};

export type CourseWorkAssessment = {
    assessmentTypeId: number;
    assessmentTypeName: string;
    markId: number | null;
    mark: number | null;
    remark: string | null;
};

export type CourseWorkStudent = {
    studentEnrolmentId: number;
    studentId: number;
    name: string;
    studentNumber: string | null;
    academicCalendarClassId?: number;
    className?: string | null;
    assessments: CourseWorkAssessment[];
    aggregation?: CourseWorkAggregation;
};

export type CourseWorkModule = {
    id: number;
    code: string | null;
    title: string | null;
    durationInHours: number | null;
    students: CourseWorkStudent[];
};

export type CourseWorkModuleWithAssessments = {
    id: number;
    code: string | null;
    title: string | null;
    durationInHours: number | null;
    assessments: CourseWorkAssessment[];
    aggregation?: CourseWorkAggregation;
};

export type CourseWorkStudentSyllabus = {
    id: number;
    code: string | null;
    title: string | null;
    modules: CourseWorkModuleWithAssessments[];
};

export type CourseWorkStudentTree = {
    academicCalendarClassId: number;
    studentEnrolmentId: number;
    student: {
        studentEnrolmentId: number;
        studentId: number;
        name: string;
        studentNumber: string | null;
    } | null;
    modeOfStudyId: number;
    syllabi: CourseWorkStudentSyllabus[];
    assessmentTypes: CourseWorkAssessmentType[];
};

export type CourseWorkSyllabus = {
    id: number;
    code: string | null;
    title: string | null;
    modules: CourseWorkModule[];
};

export type CourseWorkMarksheetSummaryItem = {
    moduleId: number;
    moduleCode: string | null;
    moduleTitle: string | null;
    completeCount: number;
    studentCount: number;
};

export type CourseWorkTree = {
    academicCalendarClassId?: number;
    classConfigId?: number;
    modeOfStudyId: number;
    syllabi: CourseWorkSyllabus[];
    assessmentTypes: CourseWorkAssessmentType[];
    students: Array<{
        studentEnrolmentId: number;
        studentId: number;
        name: string;
        studentNumber: string | null;
    }>;
    marksheetSummary?: CourseWorkMarksheetSummaryItem[];
};

export type CourseWorkClassModuleOption = {
    moduleId: number;
    syllabusId: number;
    code: string | null;
    title: string | null;
    label: string;
};

export type CourseWorkAuditLogEntry = {
    id: number;
    courseWorkMarkId: number | null;
    event: string;
    userId: number | null;
    userName: string | null;
    studentEnrolmentId: number;
    courseSyllabusModuleId: number;
    moduleCode: string | null;
    assessmentTypeId: number;
    assessmentTypeName: string | null;
    oldValues: { mark: number | null; remark: string | null } | null;
    newValues: { mark: number | null; remark: string | null } | null;
    createdAt: string | null;
};

export type CourseWorkImportPreviewMarkCell = {
    mark: number | null;
    action: 'create' | 'update' | 'skip_empty' | 'skip_duplicate' | 'fail';
    errors?: Record<string, string[]> | null;
};

export type CourseWorkImportPreviewAssessmentColumn = {
    id: number;
    name: string;
    weightPercent: number | null;
};

export type CourseWorkImportPreviewRow = {
    rowNumber: number;
    studentName: string | null;
    studentNumber: string | null;
    className: string | null;
    marks: Record<number, CourseWorkImportPreviewMarkCell>;
};

export type CourseWorkImportPreview = {
    previewToken: string;
    fileName: string;
    layout: 'wide';
    assessmentColumns: CourseWorkImportPreviewAssessmentColumn[];
    summary: {
        total: number;
        succeeded: number;
        failed: number;
        skipped: number;
        creates: number;
        updates: number;
    };
    rows: CourseWorkImportPreviewRow[];
};
