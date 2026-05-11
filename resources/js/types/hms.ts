// Hostel Management System (HMS) types

import { Staff } from "./staff";

export type HostelWardenUser = {
    full_name?: string | null;
    first_name?: string | null;
    middle_name?: string | null;
    last_name?: string | null;
};

export type HostelWarden = {
    id: number | string;
    user?: HostelWardenUser | null;
};

export type Hostel = {
    type: string;
    id: number | string;
    attributes: {
        name: string;
        type: string;
        capacity: number;
        wardenId: number | string | null;
        roomsCount: number;
        floorCount: number;
        status: string;
        location: string;
        occupiedCount: number;
        vacantCount: number;
        maintenanceCount: number;
        description?: string;
        warden?: Staff | null;
        createdAt?: string;
        updatedAt?: string;
        deletedAt?: string;
    };
};

export type HostelFiltersState = {
    search?: string | null;
    type?: string | null;
    warden?: string | null;
    with_trashed?: boolean | null;
};

export type HostelRoom = {
    type: string;
    id: number | string;
    attributes: {
        hostelId: number | string;
        hostelName?: string | null;
        name: string;
        roomType: 'single' | 'double' | 'triple' | 'suite';
        capacity: number;
        occupancy: string;
        status: 'vacant' | 'occupied' | 'maintenance';
        maxOccupancy: number;
        floorNumber?: number | null;
        description?: string | null;
        createdAt?: string;
        updatedAt?: string;
        deletedAt?: string;
    };
};

export type HostelRoomFiltersState = {
    search?: string | null;
    hostel?: string | number | null;
    with_trashed?: boolean | null;
};
