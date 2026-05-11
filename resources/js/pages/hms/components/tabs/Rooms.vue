<script setup lang="ts">
import { Bed, DoorOpen, Users, LayoutGrid } from '@lucide/vue';
import BaseButton from '@/components/core/button/BaseButton.vue';
import BaseIcon from '@/components/core/icon/BaseIcon.vue';
import Empty from '@/components/core/util/Empty.vue';
import HeadingSmall from '@/components/core/util/HeadingSmall.vue';
import HostelStatsBadge from '@/components/hms/HostelStatsBadge.vue';
import type { HostelRoom, HostelRoomFiltersState } from '@/types/hms';
import { ColorVariant } from '@/enums/colors';
import { IconName } from '@/enums/icons';
import { dangerDialog, openModal, successAlert } from '@/lib/alerts';
import { APP_MODULE_KEYS } from '@/lib/constants';
import { router } from '@inertiajs/vue3';
import { trans } from 'laravel-vue-i18n';
import { computed, onMounted, ref, watch } from 'vue';
import { useHms } from '@/composables/hms/useHms';
import { useHmsStore } from '@/store/hms/useHmsStore';
import { storeToRefs } from 'pinia';
import HostelRoomFilters from '@/components/hms/HostelRoomFilters.vue';
import { DataListProps } from '@/types/data-pagination';

const { fetchRooms, hostelRoomColumns } = useHms();
const { roomRefreshKey } = storeToRefs(useHmsStore());
const rooms  = ref<DataListProps<HostelRoom>>({data: [], links: {
    first: null,
    last: null,
    prev: null,
    next: null
}, meta: {
    total: 0, per_page: 0, current_page: 0, last_page: 0, from: 0, to: 0,
    path: null,
    links: null
}});  
const filters = ref<HostelRoomFiltersState>({});

const loadRooms = async (f: HostelRoomFiltersState = {}) => {
    const res = await fetchRooms(f);
    if (res) rooms.value = res;
};

onMounted(() => loadRooms());
watch(roomRefreshKey, () => loadRooms(filters.value));

// ── Modal helpers ────────────────────────────────────────────────────────────
const openCreate = () => openModal({ name: APP_MODULE_KEYS.hostel_rooms });
const openEdit   = (room: HostelRoom) => openModal({ name: APP_MODULE_KEYS.hostel_rooms, edit: room });

const onDelete = (room: HostelRoom) => {
    dangerDialog(() => {
        router.delete(route('hostel-rooms.destroy', String(room.id)), {
            preserveScroll: true,
            onSuccess: () => successAlert(trans('hms.room_deleted')),
        });
        return true;
    });
};

// ── Computed list & stats ────────────────────────────────────────────────────

const totalRooms    = computed(() => rooms.value.meta.total);
const totalCapacity = computed(() => rooms.value.data.reduce((sum, r) => sum + (r.attributes.capacity ?? 0), 0));
const totalMax      = computed(() => rooms.value.data.reduce((sum, r) => sum + (r.attributes.maxOccupancy ?? 0), 0));
const vacantCount   = computed(() => rooms.value.data.filter(r => r.attributes.status === 'vacant').length);


const roomTypeLabel = (t: string) => {
    const map: Record<string, string> = {
        single: trans('hms.room_type_single'),
        double: trans('hms.room_type_double'),
        triple: trans('hms.room_type_triple'),
        suite:  trans('hms.room_type_suite'),
    };
    return map[t] ?? t;
};

const statusLabel = (s: string) => {
    const map: Record<string, string> = {
        vacant:      trans('hms.room_status_vacant'),
        occupied:    trans('hms.room_status_occupied'),
        maintenance: trans('hms.room_status_maintenance'),
    };
    return map[s] ?? s;
};
</script>

<template>
    <!-- ── Page header ───────────────────────────────────────────────── -->
    <div class="my-3 flex items-center flex-wrap gap-3">
        <HostelStatsBadge
            :label="$tChoice('hms.room', 2)"
            :value="String(totalRooms)"
            :icon="DoorOpen"
            icon-class="text-indigo-500"
            value-class="text-indigo-700"
        />
        <HostelStatsBadge
            :label="$tChoice('hms.capacity', 1)"
            :value="String(totalCapacity)"
            :icon="Users"
            icon-class="text-emerald-600"
            value-class="text-emerald-700"
        />
        <HostelStatsBadge
            :label="$t('hms.max_occupancy')"
            :value="String(totalMax)"
            :icon="Bed"
            icon-class="text-amber-600"
            value-class="text-amber-700"
        />
        <HostelStatsBadge
            :label="$t('hms.room_status_vacant')"
            :value="String(vacantCount)"
            :icon="LayoutGrid"
            icon-class="text-sky-500"
            value-class="text-sky-700"
        />
    </div>
    <DataTable
        :data="rooms.data"
        :pagination="{ ...rooms.links, ...rooms.meta }"
        :columns="hostelRoomColumns()"
        :on-create="() => openCreate()"
        :disable-create="false"
        :show-archived-filter="false"
    >
        <template #head-left>
             <!-- ── Filters ───────────────────────────────────────────────────── -->
        <HostelRoomFilters :filters="filters" @change="loadRooms" />
        </template>
    </DataTable>
</template>
