export function grantedAbilitiesFromCanMap(can: Record<string, unknown> | null | undefined): string[] {
    if (!can || typeof can !== 'object' || Array.isArray(can)) {
        return [];
    }

    return Object.entries(can)
        .filter(([, granted]) => granted === true)
        .map(([ability]) => ability);
}
