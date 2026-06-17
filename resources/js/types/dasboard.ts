export type DepartmentDistribution = {
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
