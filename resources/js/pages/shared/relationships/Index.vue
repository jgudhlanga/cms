<script setup lang="ts">
import { Head } from '@inertiajs/vue3';

import PageContainer from '@/components/core/page/PageContainer.vue';
import DataTable from '@/components/core/table/DataTable.vue';
import { useRelationships } from '@/composables/shared/useRelationships';
import { AuthObject, DataFilters, DataListProps } from '@/types/data-pagination';
import CreateEdit from './partials/CreateEdit.vue';

const { createRelationshipColumns, breadcrumbs, onOpenModal } = useRelationships();

const props = defineProps<{
    relationships: DataListProps;
    trashedCount: any;
    filters: DataFilters;
    auth: AuthObject;
    errors: object;
}>();
const can = props?.auth?.can;
</script>

<template>
    <Head :title="$tChoice('trans.relationship', 2)" />
    <PageContainer :breadcrumbs="breadcrumbs">
        <DataTable
            :data="relationships.data"
            :trashed-count="trashedCount"
            :filters="filters"
            :search-url="route('relationships.index')"
            :pagination="{ ...relationships.links, ...relationships.meta }"
            :columns="createRelationshipColumns()"
            :on-create="() => onOpenModal(can['create:settings'])"
            :disable-create="!can['create:settings']"
        />
        <CreateEdit />
    </PageContainer>
</template>
