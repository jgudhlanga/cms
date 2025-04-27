<script setup lang="ts">
import { Head } from '@inertiajs/vue3';

import PageContainer from '@/components/core/page/PageContainer.vue';
import DataTable from '@/components/core/table/DataTable.vue';
import { useDivisions } from '@/composables/institution/useDivisions';
import { AuthObject, DataFilters, DataListProps } from '@/types/data-pagination';
import CreateEdit from './partials/CreateEdit.vue';

const { createDivisionColumns, breadcrumbs, onOpenModal } = useDivisions();

const props = defineProps<{
    divisions: DataListProps;
    trashedCount: any;
    filters: DataFilters;
    auth: AuthObject;
    errors: object;
}>();
const can = props?.auth?.can;
</script>

<template>
    <Head :title="$tChoice('trans.division', 2)" />
    <PageContainer :breadcrumbs="breadcrumbs">
        <DataTable
            :data="divisions.data"
            :trashed-count="trashedCount"
            :filters="filters"
            :search-url="route('divisions.index')"
            :pagination="{ ...divisions.links, ...divisions.meta }"
            :columns="createDivisionColumns()"
            :on-create="() => onOpenModal(can['create:institution-settings'])"
            :disable-create="!can['create:institution-settings']"
        />
        <CreateEdit />
    </PageContainer>
</template>
