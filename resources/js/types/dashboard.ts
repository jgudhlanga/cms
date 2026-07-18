export type DepartmentDistribution = {
    institutionDepartmentId: number;
    departmentId: number;
    departmentName: string;
    applicationCount: number;
    fullTimeCount: number;
    partTimeCount: number;
    blockReleaseCount: number;
    ojetCount: number;
    maleCount: number;
    femaleCount: number;
    disabledCount: number;
    provisionalCount: number;
    waitingCount: number;
    verifiedCount: number;
    finalCount: number;
    failedCount: number;
    departmentIntakeClassSizeTotal: number;
    color?: string;
    percentage?: string;
};

export type LevelDistribution = {
    levelId: number;
    levelName: string;
    levelCount: number;
};
export type DailyDistribution = {
    date: string;
    count: number;
};

export type EnrolmentSummary = {
    applications: number;
    offersMade: number;
    confirmed: number;
    waitlisted: number;
    provisional: number;
    failedRejected: number;
};

export type HostelDashboardSummary = {
    blocks: number;
    totalCapacity: number;
    totalRooms: number;
    occupiedBeds: number;
    availableBeds: number;
    occupancyRate: number;
    vacantRooms: number;
};

export type HostelDashboardBlock = {
    id: number;
    name: string;
    type: string | null;
    location: string | null;
    capacity: number;
    occupied: number;
    available: number;
    occupancyRate: number;
    maintenanceRooms: number;
    vacantRooms: number;
    subtitle: string;
};

export type HostelGenderSplit = {
    male: number;
    female: number;
    other: number;
};

export type HostelQueryStats = {
    open: number;
    inProgress: number;
    highPriority: number;
    resolvedThisMonth: number;
};

export type HostelApplicationStats = {
    total: number;
    pending: number;
    awaitingPayment: number;
    partiallyPaid: number;
    paid: number;
    approved: number;
    declined: number;
    paidRate: number;
};

export type HostelDashboard = {
    summary: HostelDashboardSummary;
    blocks: HostelDashboardBlock[];
    genderSplit: HostelGenderSplit;
    queryStats: HostelQueryStats;
    applicationStats: HostelApplicationStats;
};

export type StaffDashboardSummary = {
    totalStaff: number;
    academicCount: number;
    adminCount: number;
    presentToday: number | null;
    onLeaveToday: number | null;
    unfilledSessions: number | null;
};

export type StaffLecturerRatio = {
    departmentId: number;
    departmentName: string;
    studentCount: number;
    lecturerCount: number;
    ratio: number | null;
    ratioLabel: string;
    barPercent: number;
};

export type StaffCategorySegment = {
    key: string;
    label: string;
    count: number;
    percent: number;
    color: string;
};

export type StaffCategoryBreakdown = {
    segments: StaffCategorySegment[];
    fullTimeLecturers: number;
    partTimeLecturers: number;
    postgradQualified: number | null;
    onStudyLeave: number | null;
};

export type StaffGenderSplit = {
    male: number;
    female: number;
    other: number;
};

export type StaffOverCapacityRoom = {
    room: string;
    department: string;
    capacity: number;
    currentOccupancy: number;
    severity: 'critical' | 'warning';
};

export type StaffDashboard = {
    summary: StaffDashboardSummary;
    lecturerRatios: StaffLecturerRatio[];
    categoryBreakdown: StaffCategoryBreakdown;
    academicGenderSplit: StaffGenderSplit;
    overCapacityRooms: StaffOverCapacityRoom[];
    attendanceTrend: Array<{ month: string; rate: number }> | null;
};

export type AcademicDashboardSummary = {
    passRate: number | null;
    failRate: number | null;
    distinctionRate: number | null;
    probationCount: number | null;
    probationPercent: number | null;
    passRateTrend: string | null;
    failRateTrend: string | null;
    distinctionTrend: string | null;
    probationSubtext: string | null;
    markCompletionRate: number | null;
    atRiskStudentCount: number | null;
};

export type AcademicCourseWorkStatus = {
    expectedModuleResults: number;
    completeCount: number;
    completeRate: number | null;
    incompleteCount: number;
    incompleteRate: number | null;
    outstandingCount: number;
};

export type AcademicMissingMarksRow = {
    expected: number;
    incomplete: number;
    rate: number;
};

export type AcademicMissingMarksByDepartment = AcademicMissingMarksRow & {
    departmentId: number;
    departmentName: string;
};

export type AcademicMissingMarksByLevel = AcademicMissingMarksRow & {
    levelId: number;
    levelName: string;
};

export type AcademicMissingMarksByCourse = AcademicMissingMarksRow & {
    courseId: number;
    courseName: string;
};

export type AcademicMissingMarksByModule = AcademicMissingMarksRow & {
    moduleId: number;
    moduleName: string;
};

export type AcademicLecturerMarkingStat = {
    staffId: number;
    lecturerName: string;
    classesCount: number;
    expected: number;
    incomplete: number;
    incompleteRate: number;
    failRate: number;
};

export type AcademicLevelPassRate = {
    levelId: number;
    levelName: string;
    passRate: number;
    barPercent: number;
};

export type AcademicCoursePassRate = {
    courseId: number;
    courseName: string;
    passRate: number;
    barPercent: number;
};

export type AcademicGradeSegment = {
    key: string;
    label: string;
    count: number;
    percent: number;
    color: string;
};

export type AcademicDepartmentPassRate = {
    departmentId: number;
    departmentName: string;
    passRate: number;
    barPercent: number;
};

export type AcademicModuleFailureHotspot = {
    moduleId: number;
    moduleName: string;
    enrolled: number;
    failing: number;
    rate: number;
};

export type AcademicAttachmentSegment = {
    key: string;
    label: string;
    count: number;
    percent: number;
    color: string;
};

export type AcademicAttachmentStatus = {
    total: number;
    placed: number;
    awaiting: number;
    exempt: number;
    calendarYear: string;
    segments: AcademicAttachmentSegment[];
};

export type AcademicDashboard = {
    summary: AcademicDashboardSummary;
    courseWorkStatus: AcademicCourseWorkStatus;
    gradeDistribution: {
        segments: AcademicGradeSegment[];
    };
    passRateByDepartment: AcademicDepartmentPassRate[];
    passRateByLevel: AcademicLevelPassRate[];
    passRateByCourse: AcademicCoursePassRate[];
    moduleFailureHotspots: AcademicModuleFailureHotspot[];
    missingMarksByDepartment: AcademicMissingMarksByDepartment[];
    missingMarksByLevel: AcademicMissingMarksByLevel[];
    missingMarksByCourse: AcademicMissingMarksByCourse[];
    missingMarksByModule: AcademicMissingMarksByModule[];
    lecturerMarkingStats: AcademicLecturerMarkingStat[];
    attachmentStatus: AcademicAttachmentStatus | null;
    attachmentTotal: number | null;
    attachmentCalendarYear: string;
};

export type OverviewDashboardSummary = {
    passRate: number | null;
    passRateSubtext: string | null;
    markCompletionRate: number | null;
    markCompletionSubtext: string | null;
    atRiskStudents: number | null;
    atRiskSubtext: string | null;
    hostelOccupancyRate: number | null;
    hostelAvailableBeds: number | null;
    hostelSubtext: string | null;
    totalStaff: number;
    totalStaffSubtext: string | null;
};

export type OverviewEnrolmentFunnel = {
    applications: number;
    offersMade: number;
    confirmed: number;
    waitlisted: number;
    provisional: number;
    acceptanceRate: number | null;
    yieldRate: number | null;
};

export type OverviewAcademicSnapshot = {
    gradeSegments: AcademicGradeSegment[];
    topFailureHotspots: AcademicModuleFailureHotspot[];
    markCompletion: AcademicCourseWorkStatus;
};

export type OverviewQuickInsight = {
    key: string;
    message: string;
};

export type OverviewEnrolmentByDepartment = {
    departmentId: number;
    departmentName: string;
    count: number;
    barPercent: number;
};

export type OverviewPriorityAlert = {
    severity: 'critical' | 'warning' | 'info' | 'success';
    message: string;
    updatedAt: string | null;
};

export type OverviewDashboard = {
    summary: OverviewDashboardSummary;
    enrolmentFunnel: OverviewEnrolmentFunnel;
    academicSnapshot: OverviewAcademicSnapshot;
    quickInsights: OverviewQuickInsight[];
    enrolmentByDepartment: OverviewEnrolmentByDepartment[];
    priorityAlerts: OverviewPriorityAlert[];
};

export type { LecturerDashboard as TeachingDashboard } from '@/types/lecturer';
