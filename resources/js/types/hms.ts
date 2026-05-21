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

export type HostelRoomStats = {
    totalRooms: number;
    totalCapacity: number;
    totalMaxOccupancy: number;
    vacantCount: number;
};

export type HostelAllocationType = 'direct' | 'apprentice' | 'guest' | 'other';

export type HostelAllocationStatus = 'active' | 'closed' | 'pending';

export type HostelAllocation = {
    type: string;
    id: number | string;
    attributes: {
        allocationType: HostelAllocationType;
        allocationTypeLabel?: string | null;
        status: HostelAllocationStatus;
        statusLabel?: string | null;
        checkIn?: string | null;
        checkOut?: string | null;
        studentId?: number | string | null;
        studentNumber?: string | null;
        studentName?: string | null;
        gender?: string | null;
        course?: string | null;
        level?: string | null;
        hostelId?: number | string | null;
        hostelName?: string | null;
        roomId?: number | string | null;
        roomName?: string | null;
        createdAt?: string;
        updatedAt?: string;
        deletedAt?: string;
    };
};

export type HostelStudentFiltersState = {
    search?: string | null;
    gender?: number[] | null;
    hostel?: string | number | null;
    room?: string | null;
    type?: HostelAllocationType | null;
    status?: HostelAllocationStatus | null;
    with_trashed?: boolean | null;
};
