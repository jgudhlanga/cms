import { beforeAll, describe, expect, it, vi } from 'vitest';

import {
    buildDepartmentApplicationsUrl,
    enrolmentStatusFromQuery,
    enrolmentStatusOriginBackUrl,
    isEnrolmentStatusFrom,
    parseEnrolmentStatusFrom,
} from '@/lib/enrolmentStatusOrigin';

beforeAll(() => {
    vi.stubGlobal('route', (name: string, params?: Record<string, unknown>) => {
        if (name === 'enrolments.department-applications' && params?.institution_department !== undefined) {
            return `/enrolments/department-applications/${params.institution_department}`;
        }

        const routes: Record<string, string> = {
            dashboard: '/dashboard',
            'enrolments.index': '/enrolments',
        };

        const path = routes[name] ?? `/${name}`;
        const query = new URLSearchParams();
        for (const [key, value] of Object.entries(params ?? {})) {
            if (value === undefined || value === null || value === '') {
                continue;
            }
            query.set(key, String(value));
        }

        const search = query.toString();

        return search ? `${path}?${search}` : path;
    });
});

describe('parseEnrolmentStatusFrom', () => {
    it('maps dashboard and defaults to enrolments', () => {
        expect(parseEnrolmentStatusFrom('dashboard')).toBe('dashboard');
        expect(parseEnrolmentStatusFrom('enrolments')).toBe('enrolments');
        expect(parseEnrolmentStatusFrom('other')).toBe('enrolments');
        expect(parseEnrolmentStatusFrom(undefined)).toBe('enrolments');
        expect(isEnrolmentStatusFrom('dashboard')).toBe(true);
        expect(isEnrolmentStatusFrom('waiting')).toBe(false);
    });
});

describe('enrolmentStatusOriginBackUrl', () => {
    it('returns dashboard or enrolments with the intake period', () => {
        expect(enrolmentStatusOriginBackUrl('dashboard', '1')).toBe('/dashboard?intake_period_id=1');
        expect(enrolmentStatusOriginBackUrl('enrolments', '4')).toBe('/enrolments?intake_period_id=4');
        expect(enrolmentStatusOriginBackUrl('enrolments')).toBe('/enrolments');
    });
});

describe('buildDepartmentApplicationsUrl', () => {
    it('includes type, intake, mode, and from', () => {
        expect(
            buildDepartmentApplicationsUrl({
                institutionDepartmentId: 3,
                type: 'provisional',
                intakePeriodId: '1',
                modeOfStudyId: '2',
                from: 'dashboard',
            }),
        ).toBe(
            '/enrolments/department-applications/3?type=provisional&intake_period_id=1&mode_of_study_id=2&from=dashboard',
        );
    });

    it('omits invalid from values', () => {
        expect(
            buildDepartmentApplicationsUrl({
                institutionDepartmentId: 3,
                type: 'verified',
                intakePeriodId: '1',
                from: 'waiting',
            }),
        ).toBe('/enrolments/department-applications/3?type=verified&intake_period_id=1');
    });
});

describe('enrolmentStatusFromQuery', () => {
    it('only forwards a valid origin', () => {
        expect(enrolmentStatusFromQuery({ from: 'dashboard' })).toEqual({ from: 'dashboard' });
        expect(enrolmentStatusFromQuery({ from: 'enrolments' })).toEqual({ from: 'enrolments' });
        expect(enrolmentStatusFromQuery({ from: 'other' })).toEqual({});
        expect(enrolmentStatusFromQuery({})).toEqual({});
    });
});
