import type { EnrolmentApplication } from '@/types/enrolments';

export const DISABLED_QUALIFIED_ROW_CLASS = 'bg-green-100';

export const isDisabledEnrolmentGroup = (group: string | undefined | null): boolean => group === 'disabled';

/** Disabled applicants who qualify are always green; failed stays red. */
export const getDisabledQualifiedRowClass = (classListType: string | null | undefined): string => {
    if (String(classListType ?? '').toLowerCase() === 'failed') {
        return getClassListTypeRowClass('failed');
    }

    return DISABLED_QUALIFIED_ROW_CLASS;
};

export const toTitleCase = (value: string | null | undefined): string => {
    const text = String(value ?? '').trim();

    if (!text) {
        return '';
    }

    return text
        .split(/\s+/)
        .map((word) =>
            word
                .split('-')
                .map((part) => (part ? part.charAt(0).toUpperCase() + part.slice(1).toLowerCase() : part))
                .join('-'),
        )
        .join(' ');
};

export const matchesEnrolmentApplicationSearch = (application: EnrolmentApplication, query: string): boolean => {
    const q = query.trim().toLowerCase();

    if (!q) {
        return true;
    }

    const haystack = [
        application.studentName,
        application.phoneNumber,
        application.applicationTrackingNumber,
        application.email,
        application.studentNumber,
    ]
        .filter(Boolean)
        .join(' ')
        .toLowerCase();

    return haystack.includes(q);
};

export const filterEnrolmentApplications = (applications: EnrolmentApplication[], query: string): EnrolmentApplication[] => {
    const q = query.trim();

    if (!q) {
        return applications;
    }

    return applications.filter((application) => matchesEnrolmentApplicationSearch(application, q));
};

export const UNQUALIFIED_STATUS_KEY = 'unqualified';

export const applicationsExcludedFromRanking = (
    allApplications: EnrolmentApplication[],
    rankedApplications: EnrolmentApplication[],
): EnrolmentApplication[] => {
    const rankedIds = new Set(rankedApplications.map((application) => Number(application.applicationId)));

    return allApplications.filter((application) => !rankedIds.has(Number(application.applicationId)));
};

export const shortSubjectLabel = (name: string): string => {
    const map: Record<string, string> = {
        english: 'Eng',
        mathematics: 'Maths',
        'any science subject': 'Sci',
        science: 'Sci',
    };
    const key = name.trim().toLowerCase();

    if (map[key]) {
        return map[key];
    }

    return name.length > 8 ? name.slice(0, 6) : name;
};

export const gradeBadgeClass = (grade: string | null | undefined): string => {
    const g = String(grade ?? '').toUpperCase();

    if (g === 'A') {
        return 'bg-emerald-100 text-emerald-800';
    }

    if (g === 'B') {
        return 'bg-sky-100 text-sky-800';
    }

    if (g === 'C') {
        return 'bg-orange-100 text-orange-800';
    }

    return 'bg-muted text-muted-foreground';
};

export const yearSuffix = (year: string | number | null | undefined): string => {
    const value = String(year ?? '');

    return value.length >= 2 ? value.slice(-2) : value;
};

export const sittingBadgeClass = (count: number): string =>
    count > 1 ? 'bg-amber-100 text-amber-900' : 'bg-muted text-muted-foreground';

/** Class-list types that consume an intake seat (waiting / failed do not). */
export const OCCUPYING_CLASS_LIST_TYPES = ['provisional', 'verified', 'final'] as const;

export type OccupyingClassListType = (typeof OCCUPYING_CLASS_LIST_TYPES)[number];

export const occupiesIntakeSeat = (classListType: string | null | undefined): boolean =>
    OCCUPYING_CLASS_LIST_TYPES.includes(
        String(classListType ?? '').toLowerCase() as OccupyingClassListType,
    );

/**
 * Pre-generation rank bands within a gender/disability group:
 * 1..slotSize → provisional (green), slotSize+1..2×slotSize → waiting (purple), else normal.
 */
export const getRankBandClassList = (rowIndex: number, slotSize: number): string => {
    if (slotSize <= 0) {
        return '';
    }

    if (rowIndex + 1 <= slotSize) {
        return 'bg-green-100';
    }

    if (rowIndex + 1 <= slotSize * 2) {
        return 'bg-purple-100';
    }

    return '';
};

export const getClassListTypeFromRank = (rowIndex: number, slotSize: number): 'provisional' | 'waiting' | '' => {
    if (slotSize <= 0) {
        return '';
    }

    if (rowIndex + 1 <= slotSize) {
        return 'provisional';
    }

    if (rowIndex + 1 <= slotSize * 2) {
        return 'waiting';
    }

    return '';
};

export const isWithinSelectableBand = (rowIndex: number, slotSize: number): boolean =>
    slotSize > 0 && rowIndex + 1 <= slotSize * 2;

/** Row background after a class list exists, keyed by persisted type. */
export const getClassListTypeRowClass = (classListType: string | null | undefined): string => {
    switch (String(classListType ?? '').toLowerCase()) {
        case 'provisional':
            return 'bg-green-100';
        case 'waiting':
            return 'bg-purple-100';
        case 'failed':
            return 'bg-red-50/80';
        case 'verified':
            return 'bg-primary/10';
        case 'final':
            return 'bg-emerald-50';
        default:
            return '';
    }
};
