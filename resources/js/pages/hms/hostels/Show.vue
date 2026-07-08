<script setup lang="ts">
import PageContainer from '@/components/core/page/PageContainer.vue';
import { useHostelShow, type HostelShowSnapshot } from '@/composables/hms/useHostelShow';
import { openModal } from '@/lib/alerts';
import { APP_MODULE_KEYS } from '@/lib/constants';
import { hasAbility } from '@/lib/permissions';
import CreateEditHostel from '@/pages/hms/components/forms/CreateEditHostel.vue';
import CreateEditRoom from '@/pages/hms/components/forms/CreateEditRoom.vue';
import HostelFloorOccupancyChart from '@/pages/hms/hostels/partials/HostelFloorOccupancyChart.vue';
import HostelHero from '@/pages/hms/hostels/partials/HostelHero.vue';
import HostelRoomCatalogue from '@/pages/hms/hostels/partials/HostelRoomCatalogue.vue';
import HostelWardenCard from '@/pages/hms/hostels/partials/HostelWardenCard.vue';
import { useHmsStore } from '@/store/hms/useHmsStore';
import type { Hostel, HostelWardenProfile } from '@/types/hms';
import type { BreadcrumbItemInterface } from '@/types/ui';
import { Head, router } from '@inertiajs/vue3';
import { trans } from 'laravel-vue-i18n';
import { storeToRefs } from 'pinia';
import { computed, toRef, watch } from 'vue';

type HostelWardenUser = {
    first_name?: string | null;
    middle_name?: string | null;
    last_name?: string | null;
    full_name?: string | null;
};

type HostelWarden = {
    id: number | string;
    user?: HostelWardenUser | null;
};

type InertiaHostel = {
    id: number | string;
    name: string;
    location?: string | null;
    floor_count: number;
    rooms_count: number;
    capacity: number;
    status: 'active' | 'inactive';
    type?: 'male' | 'female' | 'mixed' | null;
    description?: string | null;
    warden_id?: number | string | null;
    warden?: HostelWarden | null;
    occupied_beds_sum?: number | null;
    section_count?: number | null;
    occupied_section_count?: number | null;
    available_section_count?: number | null;
    room_amenities_count?: number | null;
    section_amenities_count?: number | null;
    total_amenities_count?: number | null;
};

interface Props {
    hostel: InertiaHostel;
    wardens: Array<{ id: number | string; name: string | null }>;
    wardenProfile?: HostelWardenProfile | null;
}

const props = defineProps<Props>();

const { roomRefreshKey, hostelRefreshKey } = storeToRefs(useHmsStore());

const hostelSnapshot = computed<HostelShowSnapshot>(() => ({
    id: props.hostel.id,
    name: props.hostel.name,
    location: props.hostel.location,
    floor_count: props.hostel.floor_count,
    rooms_count: props.hostel.rooms_count,
    capacity: props.hostel.capacity,
    status: props.hostel.status,
    type: props.hostel.type,
    description: props.hostel.description,
    occupied_beds_sum: props.hostel.occupied_beds_sum,
    section_count: props.hostel.section_count,
    occupied_section_count: props.hostel.occupied_section_count,
    available_section_count: props.hostel.available_section_count,
    room_amenities_count: props.hostel.room_amenities_count,
    section_amenities_count: props.hostel.section_amenities_count,
    total_amenities_count: props.hostel.total_amenities_count,
}));

const hostelId = toRef(() => props.hostel.id);

const {
    isLoading,
    rooms,
    statusFilter,
    activeFloor,
    searchQuery,
    occupiedBeds,
    availableBeds,
    stats,
    floorTabs,
    filteredRooms,
    chartData,
    loadData,
} = useHostelShow(hostelId, hostelSnapshot);

watch(roomRefreshKey, () => {
    void loadData();
});

watch(hostelRefreshKey, () => {
    router.reload({ only: ['hostel', 'wardenProfile'] });
});

const breadcrumbs = computed<BreadcrumbItemInterface[]>(() => [
    { transChoiceKey: 'hms.title', href: route('hostels.index') },
    { title: props.hostel.name },
]);

const wardenName = computed(() => {
    if (props.wardenProfile?.name?.trim()) {
        return props.wardenProfile.name.trim();
    }

    const user = props.hostel.warden?.user;

    if (!user) {
        return trans('hms.unassigned');
    }

    if (user.full_name) {
        return user.full_name;
    }

    const parts = [user.first_name, user.middle_name, user.last_name]
        .filter((part) => Boolean(part && String(part).trim()))
        .join(' ');

    return parts || trans('hms.unassigned');
});

const hostelAsJsonApi = computed((): Hostel => ({
    type: 'hostels',
    id: props.hostel.id,
    attributes: {
        name: props.hostel.name,
        type: props.hostel.type ?? 'mixed',
        capacity: props.hostel.capacity,
        wardenId: props.hostel.warden_id ?? props.hostel.warden?.id ?? null,
        roomsCount: props.hostel.rooms_count,
        floorCount: props.hostel.floor_count,
        status: props.hostel.status,
        location: props.hostel.location ?? '',
        occupiedCount: occupiedBeds.value,
        vacantCount: availableBeds.value,
        maintenanceCount: 0,
        sectionCount: props.hostel.section_count ?? stats.value.totalSections,
        occupiedSectionCount: props.hostel.occupied_section_count ?? stats.value.occupiedSections,
        availableSectionCount: props.hostel.available_section_count ?? stats.value.availableSections,
        roomAmenitiesCount: props.hostel.room_amenities_count ?? stats.value.roomAmenities,
        sectionAmenitiesCount: props.hostel.section_amenities_count ?? stats.value.sectionAmenities,
        totalAmenitiesCount: props.hostel.total_amenities_count ?? stats.value.totalAmenities,
        description: props.hostel.description ?? '',
        wardenName: wardenName.value,
    },
}));

const canEditHostel = computed(() => hasAbility('update:hostels'));
const canAddRoom = computed(() => hasAbility('create:hostel-rooms'));

const openEditHostel = () => {
    openModal({ name: APP_MODULE_KEYS.hostels, edit: hostelAsJsonApi.value });
};

const openAddRoom = () => {
    openModal({ name: APP_MODULE_KEYS.hostel_rooms });
};
</script>

<template>
    <Head :title="hostel.name" />

    <PageContainer :breadcrumbs="breadcrumbs" :back-url="route('hostels.index')" 
    :hasBackNavigationLeading="true">
        <template #backNavigationLeading>
            <HeadingSmall :title="$t('hms.hostel_full_view')" />
        </template>
        <div class="space-y-6">
            <HostelHero
                :hostel="hostelSnapshot"
                :stats="stats"
                :occupied-beds="occupiedBeds"
                :available-beds="availableBeds"
                :can-edit="canEditHostel"
                @edit="openEditHostel"
            />

            <div class="grid grid-cols-1 gap-4 lg:grid-cols-[1fr_340px]">
                <HostelFloorOccupancyChart :chart-data="chartData" :is-loading="isLoading" />
                <HostelWardenCard
                    :warden-name="wardenName"
                    :hostel-name="hostel.name"
                    :warden-profile="wardenProfile"
                />
            </div>

            <HostelRoomCatalogue
                :rooms="rooms"
                :filtered-rooms="filteredRooms"
                :floor-tabs="floorTabs"
                :active-floor="activeFloor"
                :status-filter="statusFilter"
                :search-query="searchQuery"
                :is-loading="isLoading"
                :can-add-room="canAddRoom"
                @update:active-floor="activeFloor = $event"
                @update:status-filter="statusFilter = $event"
                @update:search-query="searchQuery = $event"
                @add-room="openAddRoom"
            />
        </div>

        <CreateEditHostel :wardens="wardens" />
        <CreateEditRoom :default-hostel-id="hostel.id" />
    </PageContainer>
</template>
