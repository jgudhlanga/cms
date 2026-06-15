import { useHms } from '@/composables/hms/useHms';
import {
    avatarColorForName,
    collectRoomFloorNumbers,
    formatFloorLabel,
    HOSTEL_SHOW_ALL_FLOORS,
    parseRoomOccupancy,
    roomAvailabilityStatus,
    studentInitials,
    type RoomAvailabilityStatus,
} from '@/lib/hms/hostelRoomDisplay';
import type { HostelAllocation, HostelRoom } from '@/types/hms';
import { trans } from 'laravel-vue-i18n';
import { computed, onMounted, ref, watch, type Ref } from 'vue';

export { HOSTEL_SHOW_ALL_FLOORS };

export type HostelShowSnapshot = {
    id: number | string;
    name: string;
    location?: string | null;
    floor_count: number;
    rooms_count: number;
    capacity: number;
    status: 'active' | 'inactive';
    type?: 'male' | 'female' | 'mixed' | null;
    description?: string | null;
    occupied_beds_sum?: number | null;
};

export type HostelRoomStudentView = {
    id: string | number;
    name: string;
    studentNumber: string;
    course: string;
    level: string;
    initials: string;
    color: string;
};

export type HostelRoomViewModel = {
    id: string | number;
    name: string;
    floorNumber: number;
    roomType: string;
    description: string | null;
    status: string;
    current: number;
    max: number;
    availability: RoomAvailabilityStatus;
    students: HostelRoomStudentView[];
};

export type HostelShowStats = {
    totalRooms: number;
    occupiedRooms: number;
    availableRooms: number;
    occupancyRate: number;
};

export type HostelFloorChartData = {
    labels: string[];
    occupied: number[];
    available: number[];
};

export type HostelShowStatusFilter = 'all' | 'available' | 'partial' | 'full';

async function fetchAllPages<T>(
    fetchPage: (url?: string) => Promise<{ data: T[]; links?: { next?: string | null } } | undefined>,
): Promise<T[]> {
    const items: T[] = [];
    let result = await fetchPage();

    if (!result) {
        return items;
    }

    items.push(...result.data);

    let next = result.links?.next ?? null;

    while (next) {
        result = await fetchPage(next);

        if (!result) {
            break;
        }

        items.push(...result.data);
        next = result.links?.next ?? null;
    }

    return items;
}

function roomResourceKey(id: string | number | null | undefined): string | null {
    if (id === null || id === undefined || id === '') {
        return null;
    }

    return String(id);
}

function buildRoomViewModels(rooms: HostelRoom[], allocations: HostelAllocation[]): HostelRoomViewModel[] {
    const studentsByRoomId = new Map<string, HostelRoomStudentView[]>();

    for (const allocation of allocations) {
        if (allocation.attributes.status !== 'active') {
            continue;
        }

        const roomKey = roomResourceKey(allocation.attributes.roomId);

        if (roomKey === null) {
            continue;
        }

        const student: HostelRoomStudentView = {
            id: allocation.attributes.studentId ?? allocation.id,
            name: allocation.attributes.studentName?.trim() || '—',
            studentNumber: allocation.attributes.studentNumber?.trim() || '—',
            course: allocation.attributes.course?.trim() || '—',
            level: allocation.attributes.level?.trim() || '',
            initials: studentInitials(allocation.attributes.studentName),
            color: avatarColorForName(allocation.attributes.studentName),
        };

        const existing = studentsByRoomId.get(roomKey) ?? [];
        existing.push(student);
        studentsByRoomId.set(roomKey, existing);
    }

    return rooms.map((room) => {
        const { current, max } = parseRoomOccupancy(
            room.attributes.occupancy,
            room.attributes.maxOccupancy,
        );
        const availability = roomAvailabilityStatus(current, max, room.attributes.status);
        const roomKey = roomResourceKey(room.id);

        return {
            id: room.id,
            name: room.attributes.name,
            floorNumber: Number(room.attributes.floorNumber ?? 0),
            roomType: room.attributes.roomType,
            description: room.attributes.description ?? null,
            status: room.attributes.status,
            current,
            max,
            availability,
            students: roomKey ? (studentsByRoomId.get(roomKey) ?? []) : [],
        };
    });
}

export function useHostelShow(hostelId: Ref<string | number>, hostelSnapshot: Ref<HostelShowSnapshot | null>) {
    const { fetchRooms, fetchHostelAllocations } = useHms();

    const isLoading = ref(true);
    const rooms = ref<HostelRoomViewModel[]>([]);
    const statusFilter = ref<HostelShowStatusFilter>('all');
    const activeFloor = ref(HOSTEL_SHOW_ALL_FLOORS);
    const searchQuery = ref('');
    const selectedRoom = ref<HostelRoomViewModel | null>(null);

    const loadData = async (): Promise<void> => {
        isLoading.value = true;

        try {
            const id = hostelId.value;
            const [roomRows, allocationRows] = await Promise.all([
                fetchAllPages((url) => fetchRooms({ hostel: id }, url)),
                fetchAllPages((url) => fetchHostelAllocations({ hostel: id, status: 'active' }, url)),
            ]);

            rooms.value = buildRoomViewModels(roomRows, allocationRows);
        } finally {
            isLoading.value = false;
        }
    };

    onMounted(() => {
        void loadData();
    });

    const occupiedBeds = computed(() => rooms.value.reduce((total, room) => total + room.current, 0));

    const totalBedCapacity = computed(() => {
        const fromRooms = rooms.value.reduce((total, room) => total + room.max, 0);
        const snapshotCapacity = hostelSnapshot.value?.capacity ?? 0;

        return fromRooms > 0 ? fromRooms : snapshotCapacity;
    });

    const availableBeds = computed(() => Math.max(0, totalBedCapacity.value - occupiedBeds.value));

    const occupancyRate = computed(() => {
        if (totalBedCapacity.value <= 0) {
            return 0;
        }

        return Math.round((occupiedBeds.value / totalBedCapacity.value) * 100);
    });

    const stats = computed<HostelShowStats>(() => {
        const occupiedRooms = rooms.value.filter((room) => room.current > 0).length;

        return {
            totalRooms: rooms.value.length,
            occupiedRooms,
            availableRooms: rooms.value.length - occupiedRooms,
            occupancyRate: occupancyRate.value,
        };
    });

    const occupiedFloors = computed(() =>
        collectRoomFloorNumbers(rooms.value.map((room) => room.floorNumber)),
    );

    const floorTabs = computed(() => occupiedFloors.value);

    const filteredRooms = computed(() => {
        let list = rooms.value;

        if (activeFloor.value !== HOSTEL_SHOW_ALL_FLOORS) {
            list = list.filter((room) => room.floorNumber === activeFloor.value);
        }

        if (statusFilter.value === 'available') {
            list = list.filter((room) => room.availability === 'available');
        } else if (statusFilter.value === 'partial') {
            list = list.filter((room) => room.availability === 'partial');
        } else if (statusFilter.value === 'full') {
            list = list.filter((room) => room.availability === 'full');
        }

        const query = searchQuery.value.trim().toLowerCase();

        if (query) {
            list = list.filter(
                (room) =>
                    room.name.toLowerCase().includes(query) ||
                    room.students.some(
                        (student) =>
                            student.name.toLowerCase().includes(query) ||
                            student.studentNumber.toLowerCase().includes(query),
                    ),
            );
        }

        return list;
    });

    const chartData = computed<HostelFloorChartData>(() => {
        const floors = occupiedFloors.value;

        if (floors.length === 0) {
            return { labels: [], occupied: [], available: [] };
        }

        return {
            labels: floors.map((floor) => formatFloorLabel(floor, trans)),
            occupied: floors.map((floor) =>
                rooms.value
                    .filter((room) => room.floorNumber === floor)
                    .reduce((total, room) => total + room.current, 0),
            ),
            available: floors.map((floor) => {
                const floorRooms = rooms.value.filter((room) => room.floorNumber === floor);
                const max = floorRooms.reduce((total, room) => total + room.max, 0);
                const occupied = floorRooms.reduce((total, room) => total + room.current, 0);

                return Math.max(0, max - occupied);
            }),
        };
    });

    watch(hostelId, () => {
        void loadData();
    });

    return {
        isLoading,
        rooms,
        statusFilter,
        activeFloor,
        searchQuery,
        selectedRoom,
        occupiedBeds,
        availableBeds,
        occupancyRate,
        stats,
        floorTabs,
        filteredRooms,
        chartData,
        loadData,
    };
}
