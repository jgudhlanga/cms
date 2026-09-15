import { describe, expect, it } from 'vitest';

import { grantedAbilitiesFromCanMap, grantedAbilitySet } from '@/lib/grantedAbilities';

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

describe('grantedAbilitySet', () => {
    it('contains only granted abilities', () => {
        const abilities = grantedAbilitySet({ 'view:users': true, 'delete:users': false });

        expect(abilities.has('view:users')).toBe(true);
        expect(abilities.has('delete:users')).toBe(false);
    });

    it('reuses the set for the same permission map and rebuilds for a new one', () => {
        const can = { 'view:users': true };

        expect(grantedAbilitySet(can)).toBe(grantedAbilitySet(can));
        expect(grantedAbilitySet({ 'view:users': true })).not.toBe(grantedAbilitySet(can));
    });

    it('is empty for missing or invalid permission maps', () => {
        expect(grantedAbilitySet(undefined).size).toBe(0);
        expect(grantedAbilitySet(null).size).toBe(0);
        expect(grantedAbilitySet([] as unknown as Record<string, unknown>).size).toBe(0);
    });
});
