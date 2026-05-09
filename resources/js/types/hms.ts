// Hostel Management System (HMS) types

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
    id: number | string;
    name: string;
    location?: string | null;
    floor_count: number;
    rooms_count: number;
    capacity: number;
    /** Number of occupied beds — appended by the backend or defaults to 0. */
    occupied_count?: number;
    status: 'active' | 'inactive';
    type?: 'male' | 'female' | 'mixed' | null;
    description?: string | null;
    warden?: HostelWarden | null;
    warden_id?: number | string | null;
};

export type HostelFiltersState = {
    search?: string | null;
    with_trashed?: boolean | null;
};
