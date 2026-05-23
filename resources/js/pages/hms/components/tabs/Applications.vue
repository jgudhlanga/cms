<script setup lang="ts">
import DataTable from '@/components/core/table/DataTable.vue';
import { useHms } from '@/composables/hms/useHms';
import { openModal } from '@/lib/alerts';
import { APP_MODULE_KEYS } from '@/lib/constants';
import { hasAbility } from '@/lib/permissions';
import { useHmsStore } from '@/store/hms/useHmsStore';
import type { HostelApplication, HostelApplicationFiltersState } from '@/types/hms';
import { DataListProps } from '@/types/data-pagination';
import { storeToRefs } from 'pinia';
import { onMounted, ref, watch } from 'vue';

const { fetchApplications, hostelApplicationColumns, isLoading } = useHms();
const { applicationRefreshKey } = storeToRefs(useHmsStore());

const applications = ref<DataListProps<HostelApplication>>({
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
const filters = ref<HostelApplicationFiltersState>({});

const loadApplications = async (f: HostelApplicationFiltersState = {}) => {
    const res = await fetchApplications(f);
    if (res) applications.value = res;
};

const loadApplicationsFromUrl = async (url: string) => {
    const res = await fetchApplications(filters.value, url);
    if (res) applications.value = res;
};

const openCreate = () => openModal({ name: APP_MODULE_KEYS.hostel_applications });

onMounted(() => loadApplications());
watch(applicationRefreshKey, () => loadApplications(filters.value));
</script>

<template>
    <DataTable
        :data="applications.data"
        :filters="filters"
        :pagination="{ ...applications.links, ...applications.meta }"
        :columns="hostelApplicationColumns()"
        :show-archived-filter="false"
        :use-api="true"
        :use-json-api="true"
        :search-url="route('v1.json.hms.hostel-applications.index')"
        :api-fetch-action="loadApplicationsFromUrl"
        :loading="isLoading"
        :show-column-filters="false"
        :on-create="hasAbility('create:hostel-applications') ? openCreate : undefined"
        :create-label="$t('hms.new_application')"
    />
</template>
