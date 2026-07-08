<script setup lang="ts">
import DataTable from '@/components/core/table/DataTable.vue';
import { useHms } from '@/composables/hms/useHms';
import { openModal } from '@/lib/alerts';
import { APP_MODULE_KEYS } from '@/lib/constants';
import { hasAbility } from '@/lib/permissions';
import { useHmsStore } from '@/store/hms/useHmsStore';
import type { HostelAmenity } from '@/types/hms';
import { DataListProps } from '@/types/data-pagination';
import { storeToRefs } from 'pinia';
import { onMounted, ref, watch } from 'vue';

const { fetchAmenities, hostelAmenityColumns, isLoading } = useHms();
const { amenityRefreshKey } = storeToRefs(useHmsStore());

const amenities = ref<DataListProps<HostelAmenity>>({
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
const filters = ref({});

const loadAmenities = async (url?: string) => {
    const res = await fetchAmenities(url);

    if (res) {
        amenities.value = res;
    }
};

const openCreate = () => openModal({ name: APP_MODULE_KEYS.hostel_amenities });

onMounted(() => loadAmenities());
watch(amenityRefreshKey, () => loadAmenities());
</script>

<template>
    <DataTable
        :data="amenities.data"
        :filters="filters"
        :pagination="{ ...amenities.links, ...amenities.meta }"
        :columns="hostelAmenityColumns()"
        :show-archived-filter="true"
        :use-api="true"
        :use-json-api="true"
        :search-url="route('v1.json.hms.hostel-amenities.index')"
        :api-fetch-action="loadAmenities"
        :loading="isLoading"
        :show-column-filters="false"
        :on-create="hasAbility('create:hostel-amenities') ? openCreate : undefined"
        :create-label="$t('hms.add_amenity')"
    />
</template>
