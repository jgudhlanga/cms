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
