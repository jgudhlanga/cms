import { describe, expect, it } from 'vitest';

import {
    departmentCalendarActions,
    formatIsoDate,
    formatIsoDateRange,
    windowStatusBadgeClass,
} from '@/lib/departmentAssessmentCalendars';
import type {
    DepartmentAssessmentCalendarPermissions,
    DepartmentAssessmentCalendarRow,
    DepartmentAssessmentCalendarWindow,
} from '@/types/department-assessment-calendars';

const allowAll: DepartmentAssessmentCalendarPermissions = { create: true, update: true, delete: true, restore: true };
const viewOnly: DepartmentAssessmentCalendarPermissions = { create: false, update: false, delete: false, restore: false };

const departmentWindow = (overrides: Partial<DepartmentAssessmentCalendarWindow> = {}): DepartmentAssessmentCalendarWindow => ({
    id: 7,
    startDate: '2026-09-01',
    endDate: '2026-09-20',
    firstNotificationDaysBefore: null,
    secondNotificationDaysBefore: null,
    dueNotificationDaysBefore: null,
    notes: null,
    trashed: false,
    started: true,
    closed: false,
    ...overrides,
});

const row = (overrides: Partial<DepartmentAssessmentCalendarRow> = {}): DepartmentAssessmentCalendarRow => ({
    globalCalendarId: 3,
    assessmentTypeName: 'Test',
    modesOfStudy: ['Full Time'],
    academicCalendarLabel: '2026 · Semester',
    globalStartDate: '2026-09-01',
    globalEndDate: '2026-09-30',
    globalClosed: false,
    defaultNotificationDays: { first: 10, second: 5, due: 0 },
    departmentCalendar: null,
    effectiveStartDate: '2026-09-01',
    effectiveEndDate: '2026-09-30',
    status: 'open',
    statusLabel: 'Open',
    ...overrides,
});

describe('departmentCalendarActions', () => {
    it('offers setting dates when the department uses the college window', () => {
        expect(departmentCalendarActions(row(), allowAll)).toEqual(['set']);
    });

    it('offers edit and revert for an active department window that has not closed', () => {
        expect(departmentCalendarActions(row({ departmentCalendar: departmentWindow() }), allowAll)).toEqual(['edit', 'revert']);
    });

    it('offers nothing once the department window has closed', () => {
        expect(departmentCalendarActions(row({ departmentCalendar: departmentWindow({ closed: true }) }), allowAll)).toEqual([]);
    });

    it('offers nothing once the college window has closed', () => {
        expect(departmentCalendarActions(row({ globalClosed: true }), allowAll)).toEqual([]);
    });

    it('offers restore and set for a removed department window', () => {
        expect(departmentCalendarActions(row({ departmentCalendar: departmentWindow({ trashed: true }) }), allowAll)).toEqual([
            'restore',
            'set',
        ]);
    });

    it('offers nothing to view-only users', () => {
        expect(departmentCalendarActions(row({ departmentCalendar: departmentWindow() }), viewOnly)).toEqual([]);
        expect(departmentCalendarActions(row(), viewOnly)).toEqual([]);
    });
});

describe('formatIsoDate', () => {
    it('formats a Y-m-d date without shifting the day', () => {
        expect(formatIsoDate('2026-09-01')).toBe('01 Sept 2026'.replace('Sept', new Date(2026, 8, 1).toLocaleDateString('en-GB', { month: 'short' })));
    });

    it('returns an empty string for missing dates and joins ranges', () => {
        expect(formatIsoDate(null)).toBe('');
        expect(formatIsoDateRange('2026-09-01', null)).toBe(formatIsoDate('2026-09-01'));
    });
});

describe('windowStatusBadgeClass', () => {
    it('returns distinct classes per status', () => {
        const classes = new Set(['open', 'extended', 'not_open', 'closed', 'not_configured'].map((status) => windowStatusBadgeClass(status as never)));

        expect(classes.size).toBe(5);
    });
});
