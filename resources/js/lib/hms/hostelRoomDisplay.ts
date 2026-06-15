export type RoomOccupancy = {
    current: number;
    max: number;
};

export type RoomAvailabilityStatus = 'available' | 'partial' | 'full' | 'maintenance';

const AVATAR_COLORS = [
    '#6366F1',
    '#DB2777',
    '#0891B2',
    '#059669',
    '#7C3AED',
    '#EA580C',
    '#0284C7',
    '#DC2626',
];

export function parseRoomOccupancy(occupancy: string, maxOccupancy?: number): RoomOccupancy {
    const parts = occupancy.split('/').map((part) => Number(part.trim()));
    const current = Number.isFinite(parts[0]) ? Math.max(0, parts[0]) : 0;
    const maxFromLabel = Number.isFinite(parts[1]) ? Math.max(0, parts[1]) : 0;
    const max = maxFromLabel > 0 ? maxFromLabel : Math.max(0, maxOccupancy ?? 0);

    return { current: Math.min(current, max || current), max };
}

export function roomAvailabilityStatus(
    current: number,
    max: number,
    roomStatus?: string,
): RoomAvailabilityStatus {
    if (roomStatus === 'maintenance') {
        return 'maintenance';
    }

    if (max <= 0 || current <= 0) {
        return 'available';
    }

    if (current >= max) {
        return 'full';
    }

    return 'partial';
}

export function studentInitials(name: string | null | undefined): string {
    if (!name?.trim()) {
        return '?';
    }

    return name
        .trim()
        .split(/\s+/)
        .map((part) => part[0])
        .join('')
        .slice(0, 2)
        .toUpperCase();
}

export function avatarColorForName(name: string | null | undefined): string {
    const value = name?.trim() ?? '';

    if (!value) {
        return AVATAR_COLORS[0];
    }

    let hash = 0;

    for (let index = 0; index < value.length; index++) {
        hash = value.charCodeAt(index) + ((hash << 5) - hash);
    }

    return AVATAR_COLORS[Math.abs(hash) % AVATAR_COLORS.length];
}

/** Sentinel for “show all floors” in hostel show filters (ground floor is 0). */
export const HOSTEL_SHOW_ALL_FLOORS = -1;

export function collectRoomFloorNumbers(floorNumbers: number[]): number[] {
    const floors = new Set<number>();

    for (const floor of floorNumbers) {
        if (Number.isFinite(floor) && floor >= 0) {
            floors.add(floor);
        }
    }

    return Array.from(floors).sort((a, b) => a - b);
}

export function formatFloorLabel(floor: number, translate: (key: string, replacements?: Record<string, unknown>) => string): string {
    if (floor === 0) {
        return translate('hms.show_floor_ground');
    }

    return translate('hms.show_floor_tab', { floor });
}

export function descriptionFeatureChips(description: string | null | undefined): string[] {
    if (!description?.trim()) {
        return [];
    }

    return description
        .split(/[,;|\n]+/)
        .map((part) => part.trim())
        .filter((part) => part.length > 0);
}
