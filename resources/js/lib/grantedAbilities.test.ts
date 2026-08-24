import { describe, expect, it } from 'vitest';

import { grantedAbilitiesFromCanMap } from '@/lib/grantedAbilities';

describe('grantedAbilitiesFromCanMap', () => {
    it('returns only abilities whose shared auth.can value is true', () => {
        expect(
            grantedAbilitiesFromCanMap({
                'view:student-applications': true,
                'viewAny:student-applications': true,
                'verify:class-lists': false,
                'confirm:class-lists': false,
            }),
        ).toEqual(['view:student-applications', 'viewAny:student-applications']);
    });

    it('ignores missing, empty, or non-object permission maps', () => {
        expect(grantedAbilitiesFromCanMap(undefined)).toEqual([]);
        expect(grantedAbilitiesFromCanMap(null)).toEqual([]);
        expect(grantedAbilitiesFromCanMap([] as unknown as Record<string, unknown>)).toEqual([]);
    });
});
