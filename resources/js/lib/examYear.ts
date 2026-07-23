import { SelectOption } from '@/types/utils';

const CURRENT_YEAR = new Date().getFullYear();
const MAX_HISTORY = 60;
const MIN_AGE_AT_EXAM = 12;
export const MAX_DISTINCT_EXAM_YEARS = 3;

export function getBirthYear(dateOfBirth: string | null | undefined): number | null {
    if (!dateOfBirth) {
        return null;
    }
    const parsed = new Date(dateOfBirth);
    if (isNaN(parsed.getTime())) {
        return null;
    }
    return parsed.getFullYear();
}

export function isValidDateOfBirth(dateOfBirth?: string | null): boolean {
    return getBirthYear(dateOfBirth) !== null;
}

export function getExamYearBounds(dateOfBirth?: string | null): { minYear: number; maxYear: number } {
    const maxYear = CURRENT_YEAR;
    const birthYear = getBirthYear(dateOfBirth ?? null);
    const minFromDob = birthYear ? birthYear + MIN_AGE_AT_EXAM : CURRENT_YEAR - MAX_HISTORY;
    const minYear = Math.max(minFromDob, CURRENT_YEAR - MAX_HISTORY);

    return { minYear, maxYear };
}

export function getExamYearOptions(dateOfBirth?: string | null): SelectOption[] {
    const { minYear, maxYear } = getExamYearBounds(dateOfBirth);
    const options: SelectOption[] = [];
    for (let year = maxYear; year >= minYear; year--) {
        options.push({ label: String(year), value: String(year) });
    }
    return options;
}

export function normalizeExamYear(value: unknown): string | null {
    if (value == null || value === '') {
        return null;
    }
    if (value instanceof Date) {
        return String(value.getFullYear());
    }
    const str = String(value).trim();
    if (/^\d{4}/.test(str)) {
        return str.slice(0, 4);
    }
    const num = Number(str);
    if (!isNaN(num) && num > 1900) {
        return String(num);
    }
    return null;
}

export function validateExamYear(year: unknown, dateOfBirth?: string | null): string | null {
    const normalized = normalizeExamYear(year);
    if (!normalized) {
        return 'Exam year is required.';
    }
    const num = Number(normalized);
    if (isNaN(num) || normalized.length !== 4) {
        return 'Exam year must be a valid 4-digit year.';
    }
    const { minYear, maxYear } = getExamYearBounds(dateOfBirth);
    if (num < minYear || num > maxYear) {
        return `Exam year must be between ${minYear} and ${maxYear}.`;
    }
    return null;
}

export function collectDistinctExamYears(...yearMaps: Array<Record<string, string> | null | undefined>): string[] {
    const years = new Set<string>();
    yearMaps.forEach((map) => {
        if (!map) {
            return;
        }
        Object.values(map).forEach((year) => {
            const normalized = normalizeExamYear(year);
            if (normalized) {
                years.add(normalized);
            }
        });
    });
    return Array.from(years);
}
