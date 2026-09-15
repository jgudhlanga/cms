import { describe, expect, it } from 'vitest';

import { formatDate, INVALID_DATE, parseStrictDate, toDate } from '@/lib/dates';

// Expected strings are what moment (en) produced for the same patterns.
describe('formatDate', () => {
    const afternoon = new Date(2026, 8, 5, 14, 7, 9);

    it.each([
        ['L', '09/05/2026'],
        ['LL', 'September 5, 2026'],
        ['LLL', 'September 5, 2026 2:07 PM'],
        ['L LT', '09/05/2026 2:07 PM'],
        ['ll', 'Sep 5, 2026'],
        ['DD MMM YY', '05 Sep 26'],
        ['DD MMM YY, HH:mm:ss', '05 Sep 26, 14:07:09'],
        ['DD MMM YYYY', '05 Sep 2026'],
        ['h:mm A', '2:07 PM'],
        ['YYYY-MM-DD', '2026-09-05'],
        ['dd [at] hh:mm a', 'Sa at 02:07 pm'],
    ])('formats %s', (pattern, expected) => {
        expect(formatDate(afternoon, pattern)).toBe(expected);
    });

    it('uses L by default', () => {
        expect(formatDate(afternoon)).toBe('09/05/2026');
    });

    it('shows midnight and noon on the 12-hour clock', () => {
        expect(formatDate(new Date(2026, 0, 1, 0, 5), 'h:mm A')).toBe('12:05 AM');
        expect(formatDate(new Date(2026, 0, 1, 12, 5), 'h:mm A')).toBe('12:05 PM');
    });

    it('keeps plain dates on their calendar day', () => {
        expect(formatDate('2026-09-15', 'LL')).toBe('September 15, 2026');
    });

    it('reads date-times with a space or a T as local time', () => {
        expect(formatDate('2026-09-15 08:30:00', 'DD MMM YY, HH:mm:ss')).toBe('15 Sep 26, 08:30:00');
        expect(formatDate('2026-09-15T08:30:00', 'HH:mm')).toBe('08:30');
    });

    it('reports invalid input like moment did', () => {
        expect(formatDate('', 'LL')).toBe(INVALID_DATE);
        expect(formatDate('not a date')).toBe(INVALID_DATE);
        expect(formatDate(null)).toBe(INVALID_DATE);
        expect(toDate('2026-02-30')).toBeNull();
    });
});

describe('parseStrictDate', () => {
    const receiptFormats = ['DD-MM-YY HH:mm:ss', 'DD-MM-YYYY HH:mm:ss', 'ISO_8601'] as const;

    it('parses bank receipt dates with two- and four-digit years', () => {
        expect(formatDate(parseStrictDate('15-09-26 10:04:05', [...receiptFormats]), 'YYYY-MM-DD HH:mm:ss')).toBe('2026-09-15 10:04:05');
        expect(formatDate(parseStrictDate('15-09-2026 10:04:05', [...receiptFormats]), 'll')).toBe('Sep 15, 2026');
    });

    it('maps two-digit years 69 and above to the 1900s', () => {
        expect(parseStrictDate('01-01-69 00:00:00', ['DD-MM-YY HH:mm:ss'])?.getFullYear()).toBe(1969);
        expect(parseStrictDate('01-01-68 00:00:00', ['DD-MM-YY HH:mm:ss'])?.getFullYear()).toBe(2068);
    });

    it('accepts ISO 8601 dates and date-times', () => {
        expect(formatDate(parseStrictDate('2026-09-15', ['YYYY-MM-DD', 'ISO_8601']), 'YYYY-MM-DD')).toBe('2026-09-15');
        expect(parseStrictDate('2026-09-15T10:00:00Z', ['ISO_8601'])).not.toBeNull();
        expect(formatDate(parseStrictDate('2026-09-15T10:00:00', ['ISO_8601']), 'HH:mm')).toBe('10:00');
    });

    it('rejects impossible dates and anything that does not match exactly', () => {
        expect(parseStrictDate('31-02-2026 10:00:00', [...receiptFormats])).toBeNull();
        expect(parseStrictDate('15/09/2026', [...receiptFormats])).toBeNull();
        expect(parseStrictDate('15-09-2026 10:00', [...receiptFormats])).toBeNull();
        expect(parseStrictDate('', [...receiptFormats])).toBeNull();
        expect(parseStrictDate(null, [...receiptFormats])).toBeNull();
    });
});
