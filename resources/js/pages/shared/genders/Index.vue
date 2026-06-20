<script setup lang="ts">
import { Head } from '@inertiajs/vue3';

import PageContainer from '@/components/core/page/PageContainer.vue';
import DataTable from '@/components/core/table/DataTable.vue';
import { useGenders } from '@/composables/shared/useGenders';
import { AuthObject, DataFilters, DataListProps } from '@/types/data-pagination';
import CreateEdit from './partials/CreateEdit.vue';

const { createGenderColumns, breadcrumbs, onOpenModal } = useGenders();

const props = defineProps<{
    genders: DataListProps;
    trashedCount: any;
    filters: DataFilters;
    auth: AuthObject;
    errors: object;
}>();
const can = props?.auth?.can;
</script>

<template>
    <Head :title="$tChoice('trans.gender', 2)" />
    <PageContainer :breadcrumbs="breadcrumbs">
        <DataTable
            :data="genders.data"
            :trashed-count="trashedCount"
            :filters="filters"
            :search-url="route('genders.index')"
            :pagination="{ ...genders.links, ...genders.meta }"
            :columns="createGenderColumns()"
            :on-create="() => onOpenModal(can['create:settings'])"
            :disable-create="!can['create:settings']"
        />
        <CreateEdit />
    </PageContainer>
</template>
