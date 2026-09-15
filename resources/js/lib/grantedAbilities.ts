export function grantedAbilitiesFromCanMap(can: Record<string, unknown> | null | undefined): string[] {
    if (!can || typeof can !== 'object' || Array.isArray(can)) {
        return [];
    }

    return Object.entries(can)
        .filter(([, granted]) => granted === true)
        .map(([ability]) => ability);
}

const EMPTY_ABILITY_SET: ReadonlySet<string> = new Set();

const abilitySets = new WeakMap<object, ReadonlySet<string>>();

/**
 * Granted abilities as a Set, cached per shared `auth.can` object. The sidebar and permission checks
 * ask hundreds of times per render, but the map only changes when page props change.
 */
export function grantedAbilitySet(can: Record<string, unknown> | null | undefined): ReadonlySet<string> {
    if (!can || typeof can !== 'object' || Array.isArray(can)) {
        return EMPTY_ABILITY_SET;
    }

    let abilities = abilitySets.get(can);

    if (!abilities) {
        abilities = new Set(grantedAbilitiesFromCanMap(can));
        abilitySets.set(can, abilities);
    }

    return abilities;
}
