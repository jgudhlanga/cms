<script setup lang="ts">
import { Head } from '@inertiajs/vue3';

import PageContainer from '@/components/core/page/PageContainer.vue';
import DataTable from '@/components/core/table/DataTable.vue';
import { useMaritalStatuses } from '@/composables/shared/useMaritalStatuses';
import { AuthObject, DataFilters, DataListProps } from '@/types/data-pagination';
import CreateEdit from './partials/CreateEdit.vue';

const { createTableColumns, breadcrumbs, onOpenModal } = useMaritalStatuses();

const props = defineProps<{
    maritalStatuses: DataListProps;
    trashedCount: any;
    filters: DataFilters;
    auth: AuthObject;
    errors: object;
}>();
const can = props?.auth?.can;
</script>

<template>
    <Head :title="$tChoice('trans.marital_status', 2)" />
    <PageContainer :breadcrumbs="breadcrumbs">
        <DataTable
            :data="maritalStatuses.data"
            :trashed-count="trashedCount"
            :filters="filters"
            :search-url="route('marital-statuses.index')"
            :pagination="{ ...maritalStatuses.links, ...maritalStatuses.meta }"
            :columns="createTableColumns()"
            :on-create="() => onOpenModal(can['create:settings'])"
            :disable-create="!can['create:settings']"
        />
        <CreateEdit />
    </PageContainer>
</template>
