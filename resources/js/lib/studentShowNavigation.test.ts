import { beforeAll, describe, expect, it, vi } from 'vitest';

import {
    buildProgramEditUrl,
    buildStudentShowUrl,
    currentPageReturnPath,
    navigationOptionsFromQuery,
    parseStudentShowFrom,
    parseStudentShowQuery,
    resolveSafeReturnUrl,
    resolveStudentShowBackDestination,
    resolveStudentShowBackUrl,
    studentShowBackPermission,
    STUDENT_SHOW_FROM_PERMISSIONS,
} from '@/lib/studentShowNavigation';

const origin = 'https://hrepoly.test';

beforeAll(() => {
    vi.stubGlobal('route', (name: string, params?: { student?: string | number }) => {
        if (name === 'students.show' && params?.student !== undefined) {
            return `/students/${params.student}`;
        }

        if (name === 'students.program-edit') {
            return `/students/program/${String(params)}/edit`;
        }

        const routes: Record<string, string> = {
            'students.index': '/students',
            'users.index': '/users',
            'maintenance.index': '/maintenance',
            'hostels.index': '/hostels',
            'academic-calendars.index': '/academic-calendars',
            'dashboard': '/dashboard',
        };

        return routes[name] ?? `/${name}`;
    });
});

describe('studentShowBackPermission', () => {
    it('maps each from slug to the correct abilities', () => {
        expect(studentShowBackPermission('students')).toBe('view:students');
        expect(studentShowBackPermission('users')).toBe('view:users');
        expect(studentShowBackPermission('maintenance')).toBe('root:manage');
        expect(studentShowBackPermission('hms')).toBe('view:hostels');
        expect(studentShowBackPermission('academic-calendar')).toEqual([
            'viewAny:academic-calendars',
            'view:academic-calendars',
        ]);
    });

    it('covers every from slug in STUDENT_SHOW_FROM_PERMISSIONS', () => {
        expect(Object.keys(STUDENT_SHOW_FROM_PERMISSIONS).sort()).toEqual([
            'academic-calendar',
            'hms',
            'maintenance',
            'students',
            'users',
        ]);
    });
});

describe('resolveSafeReturnUrl', () => {
    it('accepts same-origin relative paths', () => {
        expect(resolveSafeReturnUrl('/users?page=2', origin)).toBe('/users?page=2');
    });

    it('rejects external origins', () => {
        expect(resolveSafeReturnUrl('https://evil.test/users', origin)).toBeNull();
    });

    it('rejects protocol-relative paths', () => {
        expect(resolveSafeReturnUrl('//evil.test/users', origin)).toBeNull();
    });
});

describe('parseStudentShowFrom', () => {
    it('defaults to students for unknown values', () => {
        expect(parseStudentShowFrom('invalid')).toBe('students');
        expect(parseStudentShowFrom(null)).toBe('students');
    });

    it('parses known slugs', () => {
        expect(parseStudentShowFrom('users')).toBe('users');
        expect(parseStudentShowFrom('academic-calendar')).toBe('academic-calendar');
    });
});

describe('buildStudentShowUrl', () => {
    it('omits from when students is default', () => {
        expect(buildStudentShowUrl(22821)).toBe('/students/22821');
    });

    it('includes from and return query params', () => {
        expect(
            buildStudentShowUrl(22821, {
                from: 'users',
                return: '/users?search=test',
            }),
        ).toBe('/students/22821?from=users&return=%2Fusers%3Fsearch%3Dtest');
    });

    it('includes tab when provided', () => {
        expect(buildStudentShowUrl(22821, { from: 'users', tab: 'applications' })).toBe(
            '/students/22821?from=users&tab=applications',
        );
    });
});

describe('buildProgramEditUrl', () => {
    it('preserves navigation context', () => {
        expect(buildProgramEditUrl(99, { from: 'users', return: '/users' })).toBe(
            '/students/program/99/edit?from=users&return=%2Fusers',
        );
    });
});

describe('resolveStudentShowBackUrl', () => {
    it('prefers validated return url', () => {
        expect(resolveStudentShowBackUrl('users', '/users?search=a', origin)).toBe('/users?search=a');
    });

    it('falls back to module index from slug', () => {
        expect(resolveStudentShowBackUrl('users', null, origin)).toBe('/users');
        expect(resolveStudentShowBackUrl('maintenance', null, origin)).toBe('/maintenance');
    });
});

describe('resolveStudentShowBackDestination', () => {
    it('returns users breadcrumb for users from slug', () => {
        expect(resolveStudentShowBackDestination('users')).toEqual({
            transChoiceKey: 'user',
            href: '/users',
        });
    });

    it('returns maintenance breadcrumb for maintenance from slug', () => {
        expect(resolveStudentShowBackDestination('maintenance')).toEqual({
            transKey: 'trans.maintenance',
            href: '/maintenance',
        });
    });

    it('defaults to students breadcrumb', () => {
        expect(resolveStudentShowBackDestination('students')).toEqual({
            transChoiceKey: 'student',
            href: '/students',
        });
    });
});

describe('currentPageReturnPath', () => {
    it('returns pathname and search from page url', () => {
        expect(currentPageReturnPath('/academic-calendars/foo?bar=1', origin)).toBe('/academic-calendars/foo?bar=1');
    });
});

describe('parseStudentShowQuery', () => {
    it('parses search params', () => {
        const params = new URLSearchParams('from=hms&return=%2Fhostels&tab=applications');

        expect(parseStudentShowQuery(params)).toEqual({
            from: 'hms',
            return: '/hostels',
            tab: 'applications',
        });
    });

    it('maps to navigation options', () => {
        expect(
            navigationOptionsFromQuery({
                from: 'users',
                return: '/users',
                tab: null,
            }),
        ).toEqual({
            from: 'users',
            return: '/users',
            tab: undefined,
        });
    });
});
