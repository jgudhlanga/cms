import {
    activityDateRangeValue,
    activityEventKind,
    activityFieldChanges,
    activityGlyph,
    activityPropertyEntries,
    activityPropertyLabel,
    activitySubjectLabel,
    activityTrailFiltersEqual,
    activityTrailHasNarrowingFilters,
    activityTrailSearchParams,
    defaultActivityDateRange,
    defaultActivityTrailFilters,
    defaultSearchableActivityTrailFilters,
    groupActivitiesByDate,
    parseActivityDateRange,
} from '@/lib/activityTimeline';
import type { Audit } from '@/types/audit';
import { describe, expect, it } from 'vitest';

const audit = (overrides: Partial<Audit['attributes']> & { id?: string } = {}): Audit => {
    const { id = '1', ...attributes } = overrides;

    return {
        type: 'audit-trail',
        id,
        attributes: {
            logName: 'User',
            description: 'updated',
            subjectType: 'App\\Models\\Users\\User',
            subjectId: '1',
            causerType: 'App\\Models\\Users\\User',
            causer: 'Tinashe M.',
            properties: {},
            oldProperties: {},
            batchUuid: null,
            createdAt: '2026-08-22T12:26:00',
            updatedAt: '2026-08-22T12:26:00',
            ...attributes,
        },
    };
};

describe('activityTimeline helpers', () => {
    it('maps description to event kind', () => {
        expect(activityEventKind('created')).toBe('created');
        expect(activityEventKind('Updated')).toBe('updated');
        expect(activityEventKind('deleted')).toBe('deleted');
        expect(activityEventKind(null)).toBe('other');
    });

    it('returns a human subject label from the class basename', () => {
        expect(activitySubjectLabel('App\\Models\\Users\\User')).toBe('User');
        expect(activitySubjectLabel('StudentExamResult')).toBe('Student exam result');
        expect(activitySubjectLabel('App\\Models\\AcademicCalendars\\ClassConfig')).toBe('Class configuration');
        expect(activitySubjectLabel('App\\Models\\Institution\\IntakePeriod')).toBe('Intake period');
        expect(activitySubjectLabel('')).toBe('');
    });

    it('picks a compact glyph from the subject type', () => {
        expect(activityGlyph('App\\Models\\Users\\User', 'updated')).toBe('user');
        expect(activityGlyph('App\\Models\\Institution\\IntakePeriod', 'updated')).toBe('calendar');
        expect(activityGlyph('App\\Models\\AcademicCalendars\\ClassConfig', 'created')).toBe('academic');
        expect(activityGlyph('Widget', 'created')).toBe('created');
        expect(activityGlyph('Widget', 'updated')).toBe('updated');
        expect(activityGlyph('App\\Models\\Users\\User', 'deleted')).toBe('deleted');
        expect(activityGlyph('Widget', 'other')).toBe('other');
    });

    it('filters sensitive keys and formats property entries', () => {
        expect(
            activityPropertyEntries({
                login_count: 900,
                password: 'secret',
                remember_token: 'token',
                updated_at: '2026-08-22',
                nested: { a: 1 },
            }),
        ).toEqual([
            { key: 'login_count', value: '900' },
            { key: 'nested', value: '{"a":1}' },
        ]);

        expect(activityPropertyEntries(null)).toEqual([]);
    });

    it('title-cases database column names and drops the id suffix', () => {
        expect(activityPropertyLabel('show_transfer_path')).toBe('Show Transfer Path');
        expect(activityPropertyLabel('department_id')).toBe('Department');
        expect(activityPropertyLabel('status_id')).toBe('Status');
        expect(activityPropertyLabel('code')).toBe('Code');
    });

    it('diffs old and new properties and hides unchanged noise', () => {
        expect(
            activityFieldChanges(
                {
                    show_transfer_path: true,
                    name: 'NDIT',
                    updated_at: '2026-08-22 12:00:00',
                    password: 'secret',
                },
                {
                    show_transfer_path: false,
                    name: 'NDIT',
                    updated_at: '2026-08-21 12:00:00',
                    password: 'old-secret',
                },
            ),
        ).toEqual({
            changed: [
                {
                    key: 'show_transfer_path',
                    label: 'Show Transfer Path',
                    oldValue: 'false',
                    newValue: 'true',
                    status: 'changed',
                },
            ],
            unchangedCount: 1,
        });
    });

    it('treats created fields as additions when there is no old bag', () => {
        expect(activityFieldChanges({ code: 'ICT07' }, undefined)).toEqual({
            changed: [{ key: 'code', label: 'Code', oldValue: null, newValue: 'ICT07', status: 'added' }],
            unchangedCount: 0,
        });
    });

    it('groups activities under today, yesterday, and calendar dates', () => {
        const now = new Date(2026, 7, 22, 18, 0, 0);
        const groups = groupActivitiesByDate(
            [
                audit({ id: '1', createdAt: '2026-08-22T12:26:00' }),
                audit({ id: '2', createdAt: '2026-08-21T09:00:00' }),
                audit({ id: '3', createdAt: '2026-08-19T16:40:00' }),
                audit({ id: '4', createdAt: '2026-08-22T08:00:00' }),
            ],
            now,
        );

        expect(groups.map((group) => [group.labelKind, group.dateLabel, group.activities.map((row) => row.id)])).toEqual([
            ['today', 'Aug 22', ['1', '4']],
            ['yesterday', 'Aug 21', ['2']],
            ['date', 'Aug 19', ['3']],
        ]);
    });

    it('builds a 30-day default date range and search params', () => {
        expect(defaultActivityDateRange(new Date(2026, 7, 26, 9, 0, 0))).toEqual(['2026-07-28', '2026-08-26']);
        expect(
            activityTrailSearchParams(
                {
                    event: 'updated',
                    search: '  APP-999  ',
                    logName: 'StudentApplication',
                    from: '2026-08-01',
                    to: '2026-08-26',
                },
                2,
            ).toString(),
        ).toBe('page=2&event=updated&search=APP-999&log_name=StudentApplication&from=2026-08-01&to=2026-08-26');
    });

    it('parses date range picker values', () => {
        expect(parseActivityDateRange(['2026-08-01', '2026-08-26'])).toEqual({ from: '2026-08-01', to: '2026-08-26' });
        expect(parseActivityDateRange([new Date(2026, 7, 1), new Date(2026, 7, 26)])).toEqual({
            from: '2026-08-01',
            to: '2026-08-26',
        });
        expect(parseActivityDateRange(null)).toEqual({ from: null, to: null });
        expect(activityDateRangeValue('2026-08-01', '2026-08-26')).toEqual(['2026-08-01', '2026-08-26']);
        expect(activityTrailHasNarrowingFilters(defaultSearchableActivityTrailFilters(new Date(2026, 7, 26)))).toBe(false);
        expect(
            activityTrailHasNarrowingFilters({
                ...defaultActivityTrailFilters(),
                search: '077',
            }),
        ).toBe(true);
        expect(
            activityTrailFiltersEqual(
                defaultSearchableActivityTrailFilters(new Date(2026, 7, 26)),
                defaultSearchableActivityTrailFilters(new Date(2026, 7, 26)),
            ),
        ).toBe(true);
    });
});
