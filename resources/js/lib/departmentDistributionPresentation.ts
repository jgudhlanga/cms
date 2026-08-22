import type { DepartmentDistribution } from '@/types/dashboard';

export const REJECTION_RATE_FLAG_THRESHOLD = 0.15;

export type DepartmentDistributionSortKey = 'name_asc' | 'total_desc' | 'final_desc' | 'rejection_desc';

export type DepartmentDistributionRow = DepartmentDistribution & {
    color: string;
    percentage: string;
    percentageValue: number;
    rejectionRate: number;
    isRejectionFlagged: boolean;
};

export type DepartmentDistributionTotals = {
    male: number;
    female: number;
    disabled: number;
    fullTime: number;
    partTime: number;
    block: number;
    ojet: number;
    total: number;
    provisional: number;
    waiting: number;
    verified: number;
    final: number;
    failed: number;
    assignedDepartmentCount: number;
};

export type DepartmentDistributionKpis = {
    totalApplications: number;
    assignedDepartmentCount: number;
    finalCount: number;
    finalPercent: number;
    verifiedCount: number;
    rejectedCount: number;
    rejectedPercent: number;
    malePercent: number;
    femalePercent: number;
    disabledCount: number;
    disabledPercent: number;
};

export function colorFromDepartment(name: string, alpha = 0.7): string {
    let hash = 0;
    for (let i = 0; i < name.length; i++) {
        hash = name.charCodeAt(i) + ((hash << 5) - hash);
    }
    const r = (hash >> 16) & 255;
    const g = (hash >> 8) & 255;
    const b = hash & 255;

    return `rgba(${r}, ${g}, ${b}, ${alpha})`;
}

export function formatPercent(value: number, fractionDigits = 1): string {
    if (!Number.isFinite(value)) {
        return (0).toFixed(fractionDigits);
    }

    return value.toFixed(fractionDigits);
}

export function percentOf(part: number, whole: number, fractionDigits = 1): number {
    if (!whole || whole <= 0) {
        return 0;
    }

    const raw = (part / whole) * 100;

    return Number(raw.toFixed(fractionDigits));
}

export function rejectionRate(failedCount: number, applicationCount: number): number {
    if (!applicationCount || applicationCount <= 0) {
        return 0;
    }

    return failedCount / applicationCount;
}

export function isRejectionFlagged(failedCount: number, applicationCount: number): boolean {
    return rejectionRate(failedCount, applicationCount) > REJECTION_RATE_FLAG_THRESHOLD;
}

export function sumDepartmentDistribution(rows: DepartmentDistribution[]): DepartmentDistributionTotals {
    return rows.reduce(
        (acc, row) => {
            acc.male += Number(row.maleCount) || 0;
            acc.female += Number(row.femaleCount) || 0;
            acc.disabled += Number(row.disabledCount) || 0;
            acc.fullTime += Number(row.fullTimeCount) || 0;
            acc.partTime += Number(row.partTimeCount) || 0;
            acc.block += Number(row.blockReleaseCount) || 0;
            acc.ojet += Number(row.ojetCount) || 0;
            acc.total += Number(row.applicationCount) || 0;
            acc.provisional += Number(row.provisionalCount) || 0;
            acc.waiting += Number(row.waitingCount) || 0;
            acc.verified += Number(row.verifiedCount) || 0;
            acc.final += Number(row.finalCount) || 0;
            acc.failed += Number(row.failedCount) || 0;
            if (Number(row.institutionDepartmentId) > 0) {
                acc.assignedDepartmentCount += 1;
            }

            return acc;
        },
        {
            male: 0,
            female: 0,
            disabled: 0,
            fullTime: 0,
            partTime: 0,
            block: 0,
            ojet: 0,
            total: 0,
            provisional: 0,
            waiting: 0,
            verified: 0,
            final: 0,
            failed: 0,
            assignedDepartmentCount: 0,
        } satisfies DepartmentDistributionTotals,
    );
}

export function buildDepartmentDistributionKpis(rows: DepartmentDistribution[]): DepartmentDistributionKpis {
    const totals = sumDepartmentDistribution(rows);
    const gendered = totals.male + totals.female;

    return {
        totalApplications: totals.total,
        assignedDepartmentCount: totals.assignedDepartmentCount,
        finalCount: totals.final,
        finalPercent: percentOf(totals.final, totals.total),
        verifiedCount: totals.verified,
        rejectedCount: totals.failed,
        rejectedPercent: percentOf(totals.failed, totals.total),
        malePercent: percentOf(totals.male, gendered, 0),
        femalePercent: percentOf(totals.female, gendered, 0),
        disabledCount: totals.disabled,
        disabledPercent: percentOf(totals.disabled, totals.total),
    };
}

export function enrichDepartmentDistributionRows(rows: DepartmentDistribution[]): DepartmentDistributionRow[] {
    const total = rows.reduce((sum, row) => sum + (Number(row.applicationCount) || 0), 0);

    return rows.map((row) => {
        const applicationCount = Number(row.applicationCount) || 0;
        const failedCount = Number(row.failedCount) || 0;
        const percentageValue = total > 0 ? (applicationCount / total) * 100 : 0;
        const rate = rejectionRate(failedCount, applicationCount);

        return {
            ...row,
            color: colorFromDepartment(row.departmentName),
            percentage: formatPercent(percentageValue),
            percentageValue,
            rejectionRate: rate,
            isRejectionFlagged: isRejectionFlagged(failedCount, applicationCount),
        };
    });
}

export function filterDepartmentDistributionRows(
    rows: DepartmentDistributionRow[],
    search: string,
): DepartmentDistributionRow[] {
    const query = search.trim().toLowerCase();
    if (!query) {
        return rows;
    }

    return rows.filter((row) => row.departmentName.toLowerCase().includes(query));
}

export function sortDepartmentDistributionRows(
    rows: DepartmentDistributionRow[],
    sortKey: DepartmentDistributionSortKey,
): DepartmentDistributionRow[] {
    const sorted = [...rows];

    sorted.sort((a, b) => {
        switch (sortKey) {
            case 'total_desc':
                return b.applicationCount - a.applicationCount || a.departmentName.localeCompare(b.departmentName);
            case 'final_desc':
                return b.finalCount - a.finalCount || a.departmentName.localeCompare(b.departmentName);
            case 'rejection_desc':
                return b.rejectionRate - a.rejectionRate || b.failedCount - a.failedCount || a.departmentName.localeCompare(b.departmentName);
            case 'name_asc':
            default:
                return a.departmentName.localeCompare(b.departmentName);
        }
    });

    return sorted;
}

export function presentDepartmentDistributionRows(
    rows: DepartmentDistribution[],
    search: string,
    sortKey: DepartmentDistributionSortKey,
): DepartmentDistributionRow[] {
    const enriched = enrichDepartmentDistributionRows(rows);
    const filtered = filterDepartmentDistributionRows(enriched, search);

    return sortDepartmentDistributionRows(filtered, sortKey);
}

export function enrollmentTypeShare(count: number, total: number): number {
    if (!total || total <= 0) {
        return 0;
    }

    return Math.min(100, (count / total) * 100);
}

export function rowKey(row: Pick<DepartmentDistribution, 'institutionDepartmentId' | 'departmentId' | 'departmentName'>): string {
    if (row.institutionDepartmentId > 0) {
        return `dept-${row.institutionDepartmentId}`;
    }

    return `unassigned-${row.departmentId}-${row.departmentName}`;
}
