export type TranslateFn = (key: string, replacements?: Record<string, unknown>) => string;

const TRANSLATION_KEY = /^[a-z][a-z0-9_]*(\.[a-z][a-z0-9_]+)+$/i;

const UI_LABEL_FALLBACKS: Record<string, string> = {
    'trans.select_one': 'Select one...',
    'trans.no_options_found': 'No options found',
    'trans.activity_user_me': 'Me',
    'trans.ui_sort_department_az': 'Department (A–Z)',
    'trans.ui_sort_total_high_low': 'Total (high–low)',
    'trans.ui_sort_final_high_low': 'Final (high–low)',
    'trans.ui_sort_rejection_high_low': 'Rejection rate (high–low)',
    'trans.switch_user': 'Switch user',
    'trans.select_all': 'Select all',
    'students.filters_n_selected': ':count selected',
    'students.filter_all_genders': 'All genders',
    'academic_calendar.all_programme_semesters': 'All programme semesters',
    'hms.filter_all_types': 'All types',
    'trans.maintenance_users_filter_all_statuses': 'All statuses',
    'trans.maintenance_archives_filter_all_types': 'All types',
    'trans.maintenance_archives_filter_all_statuses': 'All statuses',
};

export const isTranslationKey = (value: string | null | undefined): boolean => {
    const label = String(value ?? '').trim();

    return TRANSLATION_KEY.test(label);
};

export const resolveUiLabel = (value: string | null | undefined, translate?: TranslateFn, replacements?: Record<string, unknown>): string => {
    const label = String(value ?? '').trim();

    if (!label) {
        return '';
    }

    if (!isTranslationKey(label)) {
        return label;
    }

    if (translate) {
        const translated = String(translate(label, replacements) ?? '').trim();

        if (translated && translated !== label) {
            return translated;
        }
    }

    const fallback = UI_LABEL_FALLBACKS[label];

    if (fallback) {
        return applyReplacements(fallback, replacements);
    }

    return humanizeTranslationKey(label);
};

const applyReplacements = (value: string, replacements?: Record<string, unknown>): string => {
    if (!replacements) {
        return value;
    }

    return Object.entries(replacements).reduce((result, [token, replacement]) => {
        return result.replaceAll(`:${token}`, String(replacement ?? ''));
    }, value);
};

const humanizeTranslationKey = (key: string): string => {
    const last = key.split('.').pop() ?? key;

    return last
        .split('_')
        .filter(Boolean)
        .map((word) => word.charAt(0).toUpperCase() + word.slice(1).toLowerCase())
        .join(' ');
};
