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
