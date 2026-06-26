import { mergeQueryParamsIntoRequestPath } from '@/lib/merge-query-into-url';
import type { Link } from '@/types/ui';

export const STUDENT_SHOW_FROM_VALUES = ['students', 'users', 'maintenance', 'hms', 'academic-calendar'] as const;

export type StudentShowFrom = (typeof STUDENT_SHOW_FROM_VALUES)[number];

export type StudentShowNavigationOptions = {
    from?: StudentShowFrom;
    return?: string;
    tab?: string;
};

export type StudentShowNavigationQuery = {
    from: StudentShowFrom;
    return: string | null;
    tab: string | null;
};

export const STUDENT_SHOW_FROM_PERMISSIONS: Record<StudentShowFrom, string | string[]> = {
    students: 'view:students',
    users: 'view:users',
    maintenance: 'root:manage',
    hms: 'view:hostels',
    'academic-calendar': ['viewAny:academic-calendars', 'view:academic-calendars'],
};

export function studentShowBackPermission(from: StudentShowFrom): string | string[] {
    return STUDENT_SHOW_FROM_PERMISSIONS[from];
}

type FromNavigationConfig = {
    backUrl: string;
    parentBreadcrumb: Link;
};

export function isStudentShowFrom(value: string | null | undefined): value is StudentShowFrom {
    return value !== null && value !== undefined && (STUDENT_SHOW_FROM_VALUES as readonly string[]).includes(value);
}

export function parseStudentShowFrom(value: string | null | undefined): StudentShowFrom {
    return isStudentShowFrom(value) ? value : 'students';
}

export function resolveSafeReturnUrl(returnParam: string | null | undefined, origin = 'https://localhost'): string | null {
    if (!returnParam?.trim()) {
        return null;
    }

    try {
        const url = returnParam.startsWith('/') ? new URL(returnParam, origin) : new URL(returnParam);

        if (url.origin !== origin) {
            return null;
        }

        if (!url.pathname.startsWith('/') || url.pathname.startsWith('//')) {
            return null;
        }

        return `${url.pathname}${url.search}`;
    } catch {
        return null;
    }
}

export function parseStudentShowQuery(searchParams: URLSearchParams): StudentShowNavigationQuery {
    return {
        from: parseStudentShowFrom(searchParams.get('from')),
        return: searchParams.get('return'),
        tab: searchParams.get('tab'),
    };
}

export function currentPageReturnPath(pageUrl: string, origin = 'https://localhost'): string {
    const parsed = new URL(pageUrl, origin);

    return `${parsed.pathname}${parsed.search}`;
}

function getFromNavigationConfig(from: StudentShowFrom): FromNavigationConfig {
    switch (from) {
        case 'users':
            return {
                backUrl: route('users.index'),
                parentBreadcrumb: { transChoiceKey: 'user', href: route('users.index') },
            };
        case 'maintenance':
            return {
                backUrl: route('maintenance.index'),
                parentBreadcrumb: { transKey: 'trans.maintenance', href: route('maintenance.index') },
            };
        case 'hms':
            return {
                backUrl: route('hostels.index'),
                parentBreadcrumb: { transChoiceKey: 'hms.hostel', transChoiceKeyIndex: 2, href: route('hostels.index') },
            };
        case 'academic-calendar':
            return {
                backUrl: route('academic-calendars.index'),
                parentBreadcrumb: {
                    transChoiceKey: 'academic_calendar.academic_calendar',
                    transChoiceKeyIndex: 2,
                    href: route('academic-calendars.index'),
                },
            };
        case 'students':
        default:
            return {
                backUrl: route('students.index'),
                parentBreadcrumb: { transChoiceKey: 'student', href: route('students.index') },
            };
    }
}

export function resolveStudentShowBackUrl(from: StudentShowFrom, returnParam?: string | null, origin?: string): string {
    const safeReturn = resolveSafeReturnUrl(returnParam, origin);

    if (safeReturn) {
        return safeReturn;
    }

    return getFromNavigationConfig(from).backUrl;
}

export function resolveStudentShowBackDestination(from: StudentShowFrom): Link {
    return getFromNavigationConfig(from).parentBreadcrumb;
}

export function buildStudentShowBreadcrumbs(from: StudentShowFrom): Link[] {
    const { parentBreadcrumb } = getFromNavigationConfig(from);

    return [
        { transKey: 'dashboard', href: route('dashboard') },
        parentBreadcrumb,
        { transChoiceKey: 'students.profile', transChoiceKeyIndex: 1 },
    ];
}

export function buildStudentShowUrl(studentId: string | number, options: StudentShowNavigationOptions = {}): string {
    const from = options.from ?? 'students';

    return mergeQueryParamsIntoRequestPath(route('students.show', { student: studentId }), {
        from: from !== 'students' ? from : undefined,
        return: options.return,
        tab: options.tab,
    });
}

export function buildProgramEditUrl(applicationId: string | number, options: StudentShowNavigationOptions = {}): string {
    const from = options.from ?? 'students';

    return mergeQueryParamsIntoRequestPath(route('students.program-edit', applicationId), {
        from: from !== 'students' ? from : undefined,
        return: options.return,
    });
}

export function navigationOptionsFromQuery(query: StudentShowNavigationQuery): StudentShowNavigationOptions {
    return {
        from: query.from,
        return: query.return ?? undefined,
        tab: query.tab ?? undefined,
    };
}
