import { DepartmentEnrolmentModeTotal } from '@/lib/json-api';
import { ModeOfStudy } from '@/types/institution';

export const MODE_ORDER = ['full time', 'part time', 'ojet', 'block release', 'block'];

const orderIndex = (name: string): number => {
    const index = MODE_ORDER.findIndex((mode) => name.toLowerCase().includes(mode));

    return index === -1 ? MODE_ORDER.length : index;
};

export function sortModesOfStudy(modes: ModeOfStudy[]): ModeOfStudy[] {
    return [...modes].sort((a, b) => {
        const safeA = orderIndex(a.attributes.name);
        const safeB = orderIndex(b.attributes.name);

        if (safeA !== safeB) {
            return safeA - safeB;
        }

        return a.attributes.name.localeCompare(b.attributes.name);
    });
}

/**
 * A department only configures modes of study against its linked course levels, but applications
 * can carry any mode. Union the two so a mode holding applications is never hidden, otherwise the
 * department page contradicts the counts on the distribution table.
 */
export function buildOrderedModes(
    configuredModes: ModeOfStudy[] | null | undefined,
    modeTotals: DepartmentEnrolmentModeTotal[] | null | undefined,
): ModeOfStudy[] {
    const configured = [...(configuredModes ?? [])];
    const seen = new Set(configured.map((mode) => String(mode.id ?? '')));
    const unconfigured: ModeOfStudy[] = [];

    for (const row of modeTotals ?? []) {
        const id = String(row.modeOfStudyId ?? '');

        if (id === '' || id === '0' || row.count <= 0 || seen.has(id)) {
            continue;
        }

        seen.add(id);
        unconfigured.push({
            type: 'mode-of-study',
            id,
            attributes: { name: row.modeOfStudyName?.trim() || `#${id}` },
        });
    }

    return sortModesOfStudy([...configured, ...unconfigured]);
}

export function pickPreferredMode(
    orderedModes: ModeOfStudy[],
    initialModeOfStudyId: string | null | undefined,
    modeTotals: DepartmentEnrolmentModeTotal[] | null | undefined,
): string {
    if (orderedModes.length === 0) {
        return '';
    }

    const requested = String(initialModeOfStudyId ?? '');
    const explicit = requested === '' ? undefined : orderedModes.find((mode) => String(mode.id ?? '') === requested);

    if (explicit) {
        return String(explicit.id ?? '');
    }

    const counts = new Map((modeTotals ?? []).map((row) => [String(row.modeOfStudyId), row.count]));
    const populated = orderedModes.find((mode) => (counts.get(String(mode.id ?? '')) ?? 0) > 0);

    return String((populated ?? orderedModes[0]).id ?? '');
}
