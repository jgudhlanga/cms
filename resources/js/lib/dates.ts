const MONTHS = ['January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December'];

const WEEKDAYS_MIN = ['Su', 'Mo', 'Tu', 'We', 'Th', 'Fr', 'Sa'];

// moment's English long-date presets, which existing callers pass (e.g. 'L', 'LL', 'L LT').
const PRESETS: Record<string, string> = {
    LT: 'h:mm A',
    L: 'MM/DD/YYYY',
    LL: 'MMMM D, YYYY',
    LLL: 'MMMM D, YYYY h:mm A',
    ll: 'MMM D, YYYY',
};

const PRESET_PATTERN = /\[[^\]]*]|LLL|LL|LT|ll|L/g;
const TOKEN_PATTERN = /\[([^\]]*)]|YYYY|YY|MMMM|MMM|MM|M|DD|D|dd|HH|H|hh|h|mm|ss|A|a/g;

export const INVALID_DATE = 'Invalid date';

const pad = (value: number, length = 2): string => String(value).padStart(length, '0');

/**
 * Parses what the API sends: Date objects, ISO strings and plain YYYY-MM-DD dates. A plain date is a local
 * calendar date (as moment treated it), not UTC midnight, so it never shifts a day in negative offsets.
 */
export function toDate(value: Date | string | number | null | undefined): Date | null {
    if (value instanceof Date) {
        return Number.isNaN(value.getTime()) ? null : value;
    }

    if (typeof value === 'number') {
        const fromNumber = new Date(value);

        return Number.isNaN(fromNumber.getTime()) ? null : fromNumber;
    }

    const text = typeof value === 'string' ? value.trim() : '';

    if (text === '') {
        return null;
    }

    const dateOnly = /^(\d{4})-(\d{2})-(\d{2})$/.exec(text);

    if (dateOnly) {
        return validDate(Number(dateOnly[1]), Number(dateOnly[2]), Number(dateOnly[3]));
    }

    // "YYYY-MM-DD HH:mm:ss" is not portable across browsers; the T form is.
    const parsed = new Date(/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}/.test(text) ? text.replace(' ', 'T') : text);

    return Number.isNaN(parsed.getTime()) ? null : parsed;
}

/**
 * Formats a date with the moment-style tokens used across the app. Unparseable input gives "Invalid date",
 * exactly as moment did.
 */
export function formatDate(value: Date | string | number | null | undefined, pattern = 'L'): string {
    const date = toDate(value);

    if (!date) {
        return INVALID_DATE;
    }

    const expanded = pattern.replace(PRESET_PATTERN, (match) => (match.startsWith('[') ? match : PRESETS[match]));
    const hours12 = date.getHours() % 12 || 12;

    return expanded.replace(TOKEN_PATTERN, (match, literal: string | undefined) => {
        if (literal !== undefined) {
            return literal;
        }

        switch (match) {
            case 'YYYY':
                return String(date.getFullYear());
            case 'YY':
                return pad(date.getFullYear() % 100);
            case 'MMMM':
                return MONTHS[date.getMonth()];
            case 'MMM':
                return MONTHS[date.getMonth()].slice(0, 3);
            case 'MM':
                return pad(date.getMonth() + 1);
            case 'M':
                return String(date.getMonth() + 1);
            case 'DD':
                return pad(date.getDate());
            case 'D':
                return String(date.getDate());
            case 'dd':
                return WEEKDAYS_MIN[date.getDay()];
            case 'HH':
                return pad(date.getHours());
            case 'H':
                return String(date.getHours());
            case 'hh':
                return pad(hours12);
            case 'h':
                return String(hours12);
            case 'mm':
                return pad(date.getMinutes());
            case 'ss':
                return pad(date.getSeconds());
            case 'A':
                return date.getHours() < 12 ? 'AM' : 'PM';
            default:
                return date.getHours() < 12 ? 'am' : 'pm';
        }
    });
}

export type StrictDateFormat = 'DD-MM-YY HH:mm:ss' | 'DD-MM-YYYY HH:mm:ss' | 'YYYY-MM-DD' | 'ISO_8601';

/**
 * Strict parsing for bank-provided dates: the first format that matches exactly and names a real calendar
 * date wins. Two-digit years follow moment's rule (69-99 are 1900s, 00-68 are 2000s).
 */
export function parseStrictDate(value: string | null | undefined, formats: StrictDateFormat[]): Date | null {
    const text = (value ?? '').trim();

    if (text === '') {
        return null;
    }

    for (const format of formats) {
        const parsed = parseWithFormat(text, format);

        if (parsed) {
            return parsed;
        }
    }

    return null;
}

function parseWithFormat(text: string, format: StrictDateFormat): Date | null {
    if (format === 'YYYY-MM-DD') {
        const match = /^(\d{4})-(\d{2})-(\d{2})$/.exec(text);

        return match ? validDate(Number(match[1]), Number(match[2]), Number(match[3])) : null;
    }

    if (format === 'ISO_8601') {
        const match = /^(\d{4})-(\d{2})-(\d{2})(?:[T ](\d{2}):(\d{2})(?::(\d{2})(?:\.\d+)?)?(Z|[+-]\d{2}:?\d{2})?)?$/.exec(text);

        if (!match) {
            return null;
        }

        const [, year, month, day, hours, minutes, seconds, offset] = match;

        if (!validDate(Number(year), Number(month), Number(day), Number(hours ?? 0), Number(minutes ?? 0), Number(seconds ?? 0))) {
            return null;
        }

        return hours === undefined ? validDate(Number(year), Number(month), Number(day)) : toDate(offset ? text.replace(' ', 'T') : text);
    }

    const match = /^(\d{2})-(\d{2})-(\d{4}|\d{2}) (\d{2}):(\d{2}):(\d{2})$/.exec(text);
    const expectsFourDigitYear = format === 'DD-MM-YYYY HH:mm:ss';

    if (!match || (match[3].length === 4) !== expectsFourDigitYear) {
        return null;
    }

    const rawYear = Number(match[3]);
    const year = expectsFourDigitYear ? rawYear : rawYear + (rawYear > 68 ? 1900 : 2000);

    return validDate(year, Number(match[2]), Number(match[1]), Number(match[4]), Number(match[5]), Number(match[6]));
}

function validDate(year: number, month: number, day: number, hours = 0, minutes = 0, seconds = 0): Date | null {
    const date = new Date(year, month - 1, day, hours, minutes, seconds);

    const matches =
        date.getFullYear() === year &&
        date.getMonth() === month - 1 &&
        date.getDate() === day &&
        date.getHours() === hours &&
        date.getMinutes() === minutes &&
        date.getSeconds() === seconds;

    return matches ? date : null;
}
