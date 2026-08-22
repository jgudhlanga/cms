import type { EnrolmentApplication } from '@/types/enrolments';

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
