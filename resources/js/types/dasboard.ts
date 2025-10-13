export type DepartmentDistribution = {
    departmentId: number;
    departmentName: string;
    applicationCount: number;
    maleCount: number;
    femaleCount: number;
    disabledCount: number;
    color?: string;
    percentage?: string;
};
export type DashboardMetrics = {
    departmentDistribution: DepartmentDistribution[];
};
