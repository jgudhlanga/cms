import { describe, expect, it } from 'vitest';
import { getExamYearBounds, isValidDateOfBirth } from '@/lib/examYear';

describe('examYear bounds', () => {
    const currentYear = new Date().getFullYear();

    it('treats missing date of birth as invalid and allows 60 years of history', () => {
        expect(isValidDateOfBirth(null)).toBe(false);
        expect(isValidDateOfBirth('')).toBe(false);
        expect(isValidDateOfBirth('not-a-date')).toBe(false);

        const { minYear, maxYear } = getExamYearBounds(null);
        expect(maxYear).toBe(currentYear);
        expect(minYear).toBe(currentYear - 60);
    });

    it('uses min age 12 when date of birth is valid', () => {
        const birthYear = currentYear - 20;
        const { minYear, maxYear } = getExamYearBounds(`${birthYear}-03-15`);

        expect(isValidDateOfBirth(`${birthYear}-03-15`)).toBe(true);
        expect(maxYear).toBe(currentYear);
        expect(minYear).toBe(birthYear + 12);
    });
});
