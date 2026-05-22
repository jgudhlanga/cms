<script setup lang="ts">
import DataTable from '@/components/core/table/DataTable.vue';
import HostelRoomStats from '@/components/hms/HostelRoomStats.vue';
import type { HostelRoom, HostelRoomFiltersState } from '@/types/hms';
import { dangerDialog, openModal, successAlert } from '@/lib/alerts';
import { APP_MODULE_KEYS } from '@/lib/constants';
import { router } from '@inertiajs/vue3';
import { trans } from 'laravel-vue-i18n';
import { onMounted, ref, watch } from 'vue';
import { useHms } from '@/composables/hms/useHms';
import { useHmsStore } from '@/store/hms/useHmsStore';
import { storeToRefs } from 'pinia';
import HostelRoomFilters from '@/components/hms/HostelRoomFilters.vue';
import { DataListProps } from '@/types/data-pagination';

const { fetchRooms, hostelRoomColumns, isLoading } = useHms();
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

const loadRoomsFromUrl = async (url: string) => {
    const res = await fetchRooms(filters.value, url);
    if (res) rooms.value = res;
};

const onRoomFiltersChange = async (f: HostelRoomFiltersState) => {
    filters.value = f;
    await loadRooms(f);
};

onMounted(() => loadRooms());
watch(roomRefreshKey, () => loadRooms(filters.value));

const openCreate = () => openModal({ name: APP_MODULE_KEYS.hostel_rooms });
</script>

<template>
    <HostelRoomStats />
    <DataTable
        :data="rooms.data"
        :filters="filters"
        :pagination="{ ...rooms.links, ...rooms.meta }"
        :columns="hostelRoomColumns()"
        :on-create="() => openCreate()"
        :disable-create="false"
        :show-archived-filter="false"
        :use-api="true"
        :use-json-api="true"
        :search-url="route('v1.json.hostel-rooms.index')"
        :api-fetch-action="loadRoomsFromUrl"
        :hide-built-in-search="true"
        :loading="isLoading"
        :show-column-filters="false"
    >
        <template #head-left>
            <HostelRoomFilters :filters="filters" @change="onRoomFiltersChange" />
        </template>
    </DataTable>
</template>
