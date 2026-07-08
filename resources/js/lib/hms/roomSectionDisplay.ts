import { IconName } from '@/enums/icons';
import type { HostelRoom } from '@/types/hms';

export type RoomSectionOccupant = {
    id: number | string;
    name: string;
    studentNumber: string;
    course: string;
};

export type RoomSectionSummary = {
    total: number;
    occupied: number;
    available: number;
};

export type RoomAmenitySummary = {
    roomAmenities: number;
    sectionAmenities: number;
    totalAmenities: number;
};

const AMENITY_ICON_BY_SLUG: Record<string, IconName> = {
    chair: IconName.chair,
    bed: IconName.bed,
    curtain: IconName.curtain,
    bic: IconName.cupboard,
    'reading-lamp': IconName.lamp,
    lamp: IconName.lamp,
    'power-outlet': IconName.plug,
    desk: IconName.desk,
};

export function amenityIconName(slugOrName: string | null | undefined): IconName {
    const normalized = String(slugOrName ?? '')
        .trim()
        .toLowerCase()
        .replace(/\s+/g, '-');

    return AMENITY_ICON_BY_SLUG[normalized] ?? IconName.settings;
}

export function summarizeRoomSections(room: HostelRoom): RoomSectionSummary {
    const total = Number(room.attributes.sectionCount ?? 0);
    const occupied = Number(room.attributes.occupiedSectionCount ?? 0);

    return {
        total,
        occupied,
        available: Math.max(0, total - occupied),
    };
}

export function summarizeRoomAmenities(room: HostelRoom): RoomAmenitySummary {
    const roomAmenities = Number(room.attributes.roomAmenitiesCount ?? 0);
    const sectionAmenities = Number(room.attributes.sectionAmenitiesCount ?? 0);
    const totalAmenities = Number(room.attributes.totalAmenitiesCount ?? roomAmenities + sectionAmenities);

    return {
        roomAmenities,
        sectionAmenities,
        totalAmenities,
    };
}
