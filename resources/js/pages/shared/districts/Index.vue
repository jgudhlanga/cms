<script setup lang="ts">
import { Head } from '@inertiajs/vue3';

import PageContainer from '@/components/core/page/PageContainer.vue';
import DataTable from '@/components/core/table/DataTable.vue';
import { useDistricts } from '@/composables/districts/useDistricts';
import { AuthObject, DataFilters, DataListProps } from '@/types/data-pagination';
import CreateEdit from './partials/CreateEdit.vue';

const { createDistrictColumns, breadcrumbs, onOpenModal } = useDistricts();

const props = defineProps<{
    districts: DataListProps;
    trashedCount: any;
    filters: DataFilters;
    auth: AuthObject;
    errors: object;
}>();
const can = props?.auth?.can;
</script>

<template>
    <Head :title="$tChoice('trans.district', 2)" />
    <PageContainer :breadcrumbs="breadcrumbs">
        <DataTable
            :data="districts.data"
            :trashed-count="trashedCount"
            :filters="filters"
            :search-url="route('districts.index')"
            :pagination="{ ...districts.links, ...districts.meta }"
            :columns="createDistrictColumns()"
            :on-create="() => onOpenModal(can['create:settings'])"
            :disable-create="!can['create:settings']"
        />
        <CreateEdit />
    </PageContainer>
</template>
