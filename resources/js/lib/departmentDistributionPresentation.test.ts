import { describe, expect, it } from 'vitest';

import type { DepartmentDistribution } from '@/types/dashboard';
import {
    buildDepartmentDistributionKpis,
    enrichDepartmentDistributionRows,
    enrollmentTypeShare,
    filterDepartmentDistributionRows,
    formatPercent,
    isRejectionFlagged,
    percentOf,
    presentDepartmentDistributionRows,
    rejectionRate,
    rowKey,
    sortDepartmentDistributionRows,
    sumDepartmentDistribution,
} from '@/lib/departmentDistributionPresentation';

function makeRow(overrides: Partial<DepartmentDistribution> = {}): DepartmentDistribution {
    return {
        institutionDepartmentId: 1,
        departmentId: 10,
        departmentName: 'Applied Arts',
        colorCode: '#2563EB',
        applicationCount: 100,
        fullTimeCount: 50,
        partTimeCount: 20,
        blockReleaseCount: 10,
        ojetCount: 20,
        maleCount: 40,
        femaleCount: 60,
        disabledCount: 2,
        provisionalCount: 10,
        waitingCount: 5,
        verifiedCount: 15,
        finalCount: 50,
        failedCount: 10,
        departmentIntakeClassSizeTotal: 80,
        ...overrides,
    };
}

describe('percentOf / formatPercent', () => {
    it('returns zero when whole is zero', () => {
        expect(percentOf(5, 0)).toBe(0);
        expect(formatPercent(Number.NaN)).toBe('0.0');
    });

    it('rounds to one decimal by default', () => {
        expect(percentOf(1, 3)).toBe(33.3);
        expect(formatPercent(33.333)).toBe('33.3');
    });
});

describe('rejection rate flag', () => {
    it('is false at or below 15%', () => {
        expect(rejectionRate(15, 100)).toBe(0.15);
        expect(isRejectionFlagged(15, 100)).toBe(false);
        expect(isRejectionFlagged(0, 0)).toBe(false);
    });

    it('is true above 15%', () => {
        expect(isRejectionFlagged(16, 100)).toBe(true);
        expect(isRejectionFlagged(3, 10)).toBe(true);
    });
});

describe('sumDepartmentDistribution', () => {
    it('aggregates counts and excludes unassigned from department count', () => {
        const totals = sumDepartmentDistribution([
            makeRow({ institutionDepartmentId: 1, applicationCount: 10, maleCount: 4, femaleCount: 6 }),
            makeRow({
                institutionDepartmentId: 0,
                departmentName: 'Unassigned',
                applicationCount: 5,
                maleCount: 2,
                femaleCount: 3,
                failedCount: 1,
            }),
        ]);

        expect(totals.total).toBe(15);
        expect(totals.male).toBe(6);
        expect(totals.female).toBe(9);
        expect(totals.failed).toBe(11);
        expect(totals.assignedDepartmentCount).toBe(1);
    });
});

describe('buildDepartmentDistributionKpis', () => {
    it('computes gender split from male+female only and handles empty data', () => {
        expect(buildDepartmentDistributionKpis([])).toMatchObject({
            totalApplications: 0,
            assignedDepartmentCount: 0,
            malePercent: 0,
            femalePercent: 0,
            finalPercent: 0,
            rejectedPercent: 0,
            disabledPercent: 0,
        });

        const kpis = buildDepartmentDistributionKpis([
            makeRow({
                applicationCount: 200,
                maleCount: 61,
                femaleCount: 39,
                finalCount: 94,
                verifiedCount: 20,
                failedCount: 21,
                disabledCount: 1,
                institutionDepartmentId: 2,
            }),
            makeRow({
                institutionDepartmentId: 0,
                departmentName: 'Unassigned',
                applicationCount: 0,
                maleCount: 0,
                femaleCount: 0,
                finalCount: 0,
                verifiedCount: 0,
                failedCount: 0,
                disabledCount: 0,
            }),
        ]);

        expect(kpis.totalApplications).toBe(200);
        expect(kpis.assignedDepartmentCount).toBe(1);
        expect(kpis.malePercent).toBe(61);
        expect(kpis.femalePercent).toBe(39);
        expect(kpis.finalPercent).toBe(47);
        expect(kpis.rejectedPercent).toBe(10.5);
        expect(kpis.disabledPercent).toBe(0.5);
        expect(kpis.verifiedCount).toBe(20);
    });
});

describe('enrich / filter / sort', () => {
    it('adds share percentage and rejection flag', () => {
        const rows = enrichDepartmentDistributionRows([
            makeRow({ applicationCount: 75, failedCount: 20 }),
            makeRow({
                institutionDepartmentId: 2,
                departmentName: 'Civil',
                applicationCount: 25,
                failedCount: 1,
            }),
        ]);

        expect(rows[0].percentage).toBe('75.0');
        expect(rows[0].color).toBe('rgba(37, 99, 235, 0.7)');
        expect(rows[0].isRejectionFlagged).toBe(true);
        expect(rows[1].percentage).toBe('25.0');
        expect(rows[1].isRejectionFlagged).toBe(false);
    });

    it('filters by department name case-insensitively', () => {
        const enriched = enrichDepartmentDistributionRows([
            makeRow({ departmentName: 'Applied Arts' }),
            makeRow({ institutionDepartmentId: 2, departmentName: 'Civil Engineering' }),
        ]);

        expect(filterDepartmentDistributionRows(enriched, 'civil')).toHaveLength(1);
        expect(filterDepartmentDistributionRows(enriched, '  ')).toHaveLength(2);
    });

    it('sorts by name, total, final, and rejection rate', () => {
        const enriched = enrichDepartmentDistributionRows([
            makeRow({
                institutionDepartmentId: 1,
                departmentName: 'Zebra',
                applicationCount: 10,
                finalCount: 2,
                failedCount: 5,
            }),
            makeRow({
                institutionDepartmentId: 2,
                departmentName: 'Alpha',
                applicationCount: 50,
                finalCount: 40,
                failedCount: 1,
            }),
            makeRow({
                institutionDepartmentId: 3,
                departmentName: 'Beta',
                applicationCount: 50,
                finalCount: 10,
                failedCount: 20,
            }),
        ]);

        expect(sortDepartmentDistributionRows(enriched, 'name_asc').map((r) => r.departmentName)).toEqual([
            'Alpha',
            'Beta',
            'Zebra',
        ]);
        expect(sortDepartmentDistributionRows(enriched, 'total_desc').map((r) => r.departmentName)).toEqual([
            'Alpha',
            'Beta',
            'Zebra',
        ]);
        expect(sortDepartmentDistributionRows(enriched, 'final_desc').map((r) => r.departmentName)).toEqual([
            'Alpha',
            'Beta',
            'Zebra',
        ]);
        expect(sortDepartmentDistributionRows(enriched, 'rejection_desc').map((r) => r.departmentName)).toEqual([
            'Zebra',
            'Beta',
            'Alpha',
        ]);
    });

    it('presentDepartmentDistributionRows combines enrich, filter, and sort', () => {
        const presented = presentDepartmentDistributionRows(
            [
                makeRow({ departmentName: 'Applied Arts', applicationCount: 10, finalCount: 1 }),
                makeRow({
                    institutionDepartmentId: 2,
                    departmentName: 'Automotive Engineering',
                    applicationCount: 90,
                    finalCount: 80,
                }),
            ],
            'auto',
            'final_desc',
        );

        expect(presented).toHaveLength(1);
        expect(presented[0].departmentName).toBe('Automotive Engineering');
        expect(presented[0].percentage).toBe('90.0');
    });
});

describe('enrollmentTypeShare / rowKey', () => {
    it('caps enrollment share at 100 and handles zero total', () => {
        expect(enrollmentTypeShare(0, 0)).toBe(0);
        expect(enrollmentTypeShare(50, 100)).toBe(50);
        expect(enrollmentTypeShare(150, 100)).toBe(100);
    });

    it('builds stable row keys for assigned and unassigned rows', () => {
        expect(rowKey(makeRow({ institutionDepartmentId: 42 }))).toBe('dept-42');
        expect(
            rowKey(
                makeRow({
                    institutionDepartmentId: 0,
                    departmentId: 0,
                    departmentName: 'Unassigned',
                }),
            ),
        ).toBe('unassigned-0-Unassigned');
    });
});
