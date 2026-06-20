<script setup lang="ts">
import { Head } from '@inertiajs/vue3';

import PageContainer from '@/components/core/page/PageContainer.vue';
import DataTable from '@/components/core/table/DataTable.vue';
import { useSponsorTypes } from '@/composables/shared/useSponsorTypes';
import { AuthObject, DataFilters, DataListProps } from '@/types/data-pagination';
import CreateEdit from './partials/CreateEdit.vue';

const { createSponsorTypeColumns, breadcrumbs, onOpenModal } = useSponsorTypes();

const props = defineProps<{
    sponsorTypes: DataListProps;
    trashedCount: any;
    filters: DataFilters;
    auth: AuthObject;
    errors: object;
}>();
const can = props?.auth?.can;
</script>

<template>
    <Head :title="$tChoice('trans.sponsor_type', 2)" />
    <PageContainer :breadcrumbs="breadcrumbs">
        <DataTable
            :data="sponsorTypes.data"
            :trashed-count="trashedCount"
            :filters="filters"
            :search-url="route('sponsor-types.index')"
            :pagination="{ ...sponsorTypes.links, ...sponsorTypes.meta }"
            :columns="createSponsorTypeColumns()"
            :on-create="() => onOpenModal(can['create:settings'])"
            :disable-create="!can['create:settings']"
        />
        <CreateEdit />
    </PageContainer>
</template>
