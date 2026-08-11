export type ActivityEventKind = 'created' | 'updated' | 'other';

export type ActivityEventFilter = 'all' | 'created' | 'updated';

export type ActivityPropertyEntry = {
    key: string;
    value: string;
};

const SENSITIVE_PROPERTY_KEYS = new Set([
    'password',
    'password_confirmation',
    'current_password',
    'remember_token',
]);

export const activityEventKind = (description: string | null | undefined): ActivityEventKind => {
    const normalized = String(description ?? '')
        .trim()
        .toLowerCase();

    if (normalized === 'created') {
        return 'created';
    }

    if (normalized === 'updated') {
        return 'updated';
    }

    return 'other';
};

export const activitySubjectLabel = (subjectType: string | null | undefined): string => {
    const value = String(subjectType ?? '').trim();

    if (!value) {
        return '';
    }

    const segments = value.split(/\\|\//);

    return segments[segments.length - 1] || value;
};

export const activityPropertyEntries = (
    properties: Record<string, unknown> | null | undefined,
): ActivityPropertyEntry[] => {
    if (!properties || typeof properties !== 'object') {
        return [];
    }

    return Object.entries(properties)
        .filter(([key]) => !SENSITIVE_PROPERTY_KEYS.has(key.toLowerCase()))
        .map(([key, value]) => ({
            key,
            value: formatPropertyValue(value),
        }));
};

const formatPropertyValue = (value: unknown): string => {
    if (value === null || value === undefined) {
        return '';
    }

    if (typeof value === 'object') {
        try {
            return JSON.stringify(value);
        } catch {
            return String(value);
        }
    }

    return String(value);
};
