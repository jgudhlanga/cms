import { describe, expect, it } from 'vitest';

import { isValidDepartmentColor, resolveDepartmentColor } from '@/lib/departmentColor';

describe('resolveDepartmentColor', () => {
    it('uses stored color code when present', () => {
        expect(resolveDepartmentColor('#2563EB', 'Applied Arts', 1)).toBe('#2563EB');
    });

    it('falls back to department name hash when color is missing', () => {
        expect(resolveDepartmentColor(null, 'Applied Arts', 1)).toMatch(/^rgba?\(/);
    });
});

describe('isValidDepartmentColor', () => {
    it('accepts valid hex colors', () => {
        expect(isValidDepartmentColor('#ABCDEF')).toBe(true);
        expect(isValidDepartmentColor('ABCDEF')).toBe(false);
    });
});
