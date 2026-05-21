<script setup lang="ts">
import DataTable from '@/components/core/table/DataTable.vue';
import HostelStudentFilters from '@/components/hms/HostelStudentFilters.vue';
import { useHms } from '@/composables/hms/useHms';
import { useHmsStore } from '@/store/hms/useHmsStore';
import type { HostelAllocation, HostelStudentFiltersState } from '@/types/hms';
import { DataListProps } from '@/types/data-pagination';
import { storeToRefs } from 'pinia';
import { onMounted, ref, watch } from 'vue';

const { fetchHostelAllocations, hostelStudentColumns, isLoading } = useHms();
const { studentRefreshKey } = storeToRefs(useHmsStore());

const allocations = ref<DataListProps<HostelAllocation>>({
    data: [],
    links: { first: null, last: null, prev: null, next: null },
    meta: {
        total: 0,
        per_page: 0,
        current_page: 0,
        last_page: 0,
        from: 0,
        to: 0,
        path: null,
        links: null,
    },
});
const filters = ref<HostelStudentFiltersState>({});

const loadAllocations = async (f: HostelStudentFiltersState = {}) => {
    const res = await fetchHostelAllocations(f);
    if (res) allocations.value = res;
};

const loadAllocationsFromUrl = async (url: string) => {
    const res = await fetchHostelAllocations(filters.value, url);
    if (res) allocations.value = res;
};

const onFiltersChange = async (f: HostelStudentFiltersState) => {
    filters.value = f;
    await loadAllocations(f);
};

onMounted(() => loadAllocations());
watch(studentRefreshKey, () => loadAllocations(filters.value));
</script>

<template>
    <DataTable
        :data="allocations.data"
        :filters="filters"
        :pagination="{ ...allocations.links, ...allocations.meta }"
        :columns="hostelStudentColumns()"
        :show-archived-filter="false"
        :use-api="true"
        :search-url="route('v1.hms.hostel-allocations')"
        :api-fetch-action="loadAllocationsFromUrl"
        :hide-built-in-search="true"
        :loading="isLoading"
        :show-column-filters="false"
    >
        <template #head-left>
            <HostelStudentFilters :filters="filters" @change="onFiltersChange" />
        </template>
    </DataTable>
</template>
