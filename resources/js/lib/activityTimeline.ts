export type ActivityEventKind = 'created' | 'updated' | 'deleted' | 'other';

export type ActivityEventFilter = 'all' | 'created' | 'updated' | 'deleted';

export type ActivityGlyph = 'user' | 'calendar' | 'academic' | 'created' | 'updated' | 'deleted' | 'other';

export type ActivityDateLabelKind = 'today' | 'yesterday' | 'date';

export type ActivityPropertyEntry = {
    key: string;
    value: string;
};

export type ActivityFieldChange = {
    key: string;
    label: string;
    oldValue: string | null;
    newValue: string | null;
    status: 'added' | 'removed' | 'changed' | 'unchanged';
};

export type ActivityDateGroup<T extends { attributes: { createdAt: string } }> = {
    key: string;
    labelKind: ActivityDateLabelKind;
    dateLabel: string;
    activities: T[];
};

const SENSITIVE_PROPERTY_KEYS = new Set(['password', 'password_confirmation', 'current_password', 'remember_token']);

const NOISE_PROPERTY_KEYS = new Set(['created_at', 'updated_at', 'deleted_at']);

const SUBJECT_LABELS: Record<string, string> = {
    AcademicCalendar: 'Academic calendar',
    AcademicCalendarClass: 'Class',
    ClassConfig: 'Class configuration',
    IntakePeriod: 'Intake period',
    StudentApplication: 'Student application',
    UserPreference: 'User preference',
};

const SUBJECT_GLYPHS: Array<[RegExp, ActivityGlyph]> = [
    [/user|login|session|auth|preference/i, 'user'],
    [/calendar|intake|period|schedule/i, 'calendar'],
    [/class|course|level|syllabus|exam|student|enrol/i, 'academic'],
];

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

    if (normalized === 'deleted') {
        return 'deleted';
    }

    return 'other';
};

export const activitySubjectLabel = (subjectType: string | null | undefined): string => {
    const basename = subjectBasename(subjectType);

    if (!basename) {
        return '';
    }

    return SUBJECT_LABELS[basename] ?? humanizeBasename(basename);
};

const subjectBasename = (subjectType: string | null | undefined): string => {
    const value = String(subjectType ?? '').trim();

    if (!value) {
        return '';
    }

    const segments = value.split(/\\|\//);

    return segments[segments.length - 1] || value;
};

export const activityGlyph = (subjectType: string | null | undefined, kind: ActivityEventKind): ActivityGlyph => {
    if (kind === 'deleted') {
        return 'deleted';
    }

    const haystack = `${subjectBasename(subjectType)} ${activitySubjectLabel(subjectType)}`;

    for (const [pattern, glyph] of SUBJECT_GLYPHS) {
        if (pattern.test(haystack)) {
            return glyph;
        }
    }

    if (kind === 'created' || kind === 'updated') {
        return kind;
    }

    return 'other';
};

export const activityPropertyEntries = (properties: Record<string, unknown> | null | undefined): ActivityPropertyEntry[] => {
    if (!properties || typeof properties !== 'object') {
        return [];
    }

    return Object.entries(properties)
        .filter(([key]) => isVisiblePropertyKey(key))
        .map(([key, value]) => ({
            key,
            value: formatPropertyValue(value),
        }));
};

export const activityPropertyLabel = (key: string): string => {
    const withoutForeignKeySuffix = key.replace(/_id$/i, '');
    const words = withoutForeignKeySuffix
        .replace(/([a-z0-9])([A-Z])/g, '$1 $2')
        .split(/[_\s]+/)
        .filter(Boolean);

    if (words.length === 0) {
        return key;
    }

    return words.map((word) => word.charAt(0).toUpperCase() + word.slice(1).toLowerCase()).join(' ');
};

export const activityFieldChanges = (
    properties: Record<string, unknown> | null | undefined,
    oldProperties: Record<string, unknown> | null | undefined,
): { changed: ActivityFieldChange[]; unchangedCount: number } => {
    const next = properties && typeof properties === 'object' ? properties : {};
    const previous = oldProperties && typeof oldProperties === 'object' ? oldProperties : {};
    const keys = [...new Set([...Object.keys(next), ...Object.keys(previous)])].filter(isVisiblePropertyKey);

    const changed: ActivityFieldChange[] = [];
    let unchangedCount = 0;

    for (const key of keys) {
        const hasNew = Object.prototype.hasOwnProperty.call(next, key);
        const hasOld = Object.prototype.hasOwnProperty.call(previous, key);
        const newValue = hasNew ? formatPropertyValue(next[key]) : null;
        const oldValue = hasOld ? formatPropertyValue(previous[key]) : null;

        if (hasNew && hasOld && newValue === oldValue) {
            unchangedCount += 1;
            continue;
        }

        changed.push({
            key,
            label: activityPropertyLabel(key),
            oldValue: hasOld ? oldValue : null,
            newValue: hasNew ? newValue : null,
            status: fieldChangeStatus(hasOld, hasNew),
        });
    }

    return { changed, unchangedCount };
};

export type ActivityTrailFiltersState = {
    event: ActivityEventFilter;
    search: string;
    logName: string | null;
    from: string | null;
    to: string | null;
};

export const defaultActivityTrailFilters = (): ActivityTrailFiltersState => ({
    event: 'all',
    search: '',
    logName: null,
    from: null,
    to: null,
});

export const defaultSearchableActivityTrailFilters = (now: Date = new Date()): ActivityTrailFiltersState => {
    const [from, to] = defaultActivityDateRange(now);

    return {
        ...defaultActivityTrailFilters(),
        from,
        to,
    };
};

export const defaultActivityDateRange = (now: Date = new Date()): [string, string] => {
    const end = startOfLocalDay(now);
    const start = new Date(end);
    start.setDate(start.getDate() - 29);

    return [localDateKey(start), localDateKey(end)];
};

export const activityTrailSearchParams = (filters: ActivityTrailFiltersState, page: number): URLSearchParams => {
    const params = new URLSearchParams({ page: String(page) });

    if (filters.event !== 'all') {
        params.set('event', filters.event);
    }

    const search = filters.search.trim();

    if (search !== '') {
        params.set('search', search);
    }

    if (filters.logName) {
        params.set('log_name', filters.logName);
    }

    if (filters.from) {
        params.set('from', filters.from);
    }

    if (filters.to) {
        params.set('to', filters.to);
    }

    return params;
};

export const parseActivityDateRange = (value: unknown): { from: string | null; to: string | null } => {
    if (!Array.isArray(value) || value.length < 2) {
        return { from: null, to: null };
    }

    return {
        from: toDateKey(value[0]),
        to: toDateKey(value[1]),
    };
};

export const activityDateRangeValue = (from: string | null, to: string | null): [string, string] | null => {
    if (!from || !to) {
        return null;
    }

    return [from, to];
};

export const activityTrailFiltersEqual = (left: ActivityTrailFiltersState, right: ActivityTrailFiltersState): boolean =>
    left.event === right.event &&
    left.search.trim() === right.search.trim() &&
    left.logName === right.logName &&
    left.from === right.from &&
    left.to === right.to;

export const activityTrailHasNarrowingFilters = (filters: ActivityTrailFiltersState): boolean => {
    return filters.event !== 'all' || filters.search.trim() !== '' || Boolean(filters.logName);
};

const toDateKey = (value: unknown): string | null => {
    if (typeof value === 'string') {
        const match = value.trim().match(/^(\d{4}-\d{2}-\d{2})/);

        return match?.[1] ?? null;
    }

    if (value instanceof Date && !Number.isNaN(value.getTime())) {
        return localDateKey(value);
    }

    return null;
};

export const groupActivitiesByDate = <T extends { attributes: { createdAt: string } }>(
    activities: T[],
    now: Date = new Date(),
): ActivityDateGroup<T>[] => {
    const groups = new Map<string, ActivityDateGroup<T>>();
    const today = startOfLocalDay(now);

    for (const activity of activities) {
        const created = parseLocalDate(activity.attributes.createdAt);

        if (!created) {
            continue;
        }

        const key = localDateKey(created);
        const existing = groups.get(key);

        if (existing) {
            existing.activities.push(activity);
            continue;
        }

        groups.set(key, {
            key,
            labelKind: dateLabelKind(created, today),
            dateLabel: formatMonthDay(created),
            activities: [activity],
        });
    }

    return [...groups.values()];
};

const humanizeBasename = (basename: string): string => {
    const spaced = basename
        .replace(/_/g, ' ')
        .replace(/([a-z0-9])([A-Z])/g, '$1 $2')
        .trim();

    if (!spaced) {
        return basename;
    }

    return spaced
        .split(/\s+/)
        .map((word, index) => (index === 0 ? capitalize(word) : word.toLowerCase()))
        .join(' ');
};

const capitalize = (value: string): string => {
    if (!value) {
        return value;
    }

    return value.charAt(0).toUpperCase() + value.slice(1);
};

const isVisiblePropertyKey = (key: string): boolean => {
    const normalized = key.toLowerCase();

    return !SENSITIVE_PROPERTY_KEYS.has(normalized) && !NOISE_PROPERTY_KEYS.has(normalized);
};

const fieldChangeStatus = (hasOld: boolean, hasNew: boolean): ActivityFieldChange['status'] => {
    if (hasOld && hasNew) {
        return 'changed';
    }

    if (hasNew) {
        return 'added';
    }

    return 'removed';
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

const parseLocalDate = (value: string): Date | null => {
    const parsed = new Date(value);

    if (Number.isNaN(parsed.getTime())) {
        return null;
    }

    return parsed;
};

const startOfLocalDay = (value: Date): Date => {
    return new Date(value.getFullYear(), value.getMonth(), value.getDate());
};

export const localDateKey = (value: Date): string => {
    const year = value.getFullYear();
    const month = String(value.getMonth() + 1).padStart(2, '0');
    const day = String(value.getDate()).padStart(2, '0');

    return `${year}-${month}-${day}`;
};

const dateLabelKind = (created: Date, today: Date): ActivityDateLabelKind => {
    const diffDays = Math.round((today.getTime() - startOfLocalDay(created).getTime()) / 86_400_000);

    if (diffDays === 0) {
        return 'today';
    }

    if (diffDays === 1) {
        return 'yesterday';
    }

    return 'date';
};

const formatMonthDay = (value: Date): string => {
    return value.toLocaleDateString('en-US', { month: 'short', day: 'numeric' });
};
