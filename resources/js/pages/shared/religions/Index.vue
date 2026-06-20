<script setup lang="ts">
import { Head } from '@inertiajs/vue3';

import PageContainer from '@/components/core/page/PageContainer.vue';
import DataTable from '@/components/core/table/DataTable.vue';
import { useReligions } from '@/composables/shared/useReligions';
import { AuthObject, DataFilters, DataListProps } from '@/types/data-pagination';
import CreateEdit from './partials/CreateEdit.vue';

const { createReligionColumns, breadcrumbs, onOpenModal } = useReligions();

const props = defineProps<{
    religions: DataListProps;
    trashedCount: any;
    filters: DataFilters;
    auth: AuthObject;
    errors: object;
}>();
const can = props?.auth?.can;
</script>

<template>
    <Head :title="$tChoice('trans.religion', 2)" />
    <PageContainer :breadcrumbs="breadcrumbs">
        <DataTable
            :data="religions.data"
            :trashed-count="trashedCount"
            :filters="filters"
            :search-url="route('religions.index')"
            :pagination="{ ...religions.links, ...religions.meta }"
            :columns="createReligionColumns()"
            :on-create="() => onOpenModal(can['create:settings'])"
            :disable-create="!can['create:settings']"
        />
        <CreateEdit />
    </PageContainer>
</template>
