<script setup lang="ts">
import { Head } from '@inertiajs/vue3';

import PageContainer from '@/components/core/page/PageContainer.vue';
import DataTable from '@/components/core/table/DataTable.vue';
import { useDepartments } from '@/composables/institution/useDepartments';
import { AuthObject, DataFilters, DataListProps } from '@/types/data-pagination';
import CreateEdit from './partials/CreateEdit.vue';

const { createDepartmentColumns, breadcrumbs, onOpenModal } = useDepartments();

const props = defineProps<{
    departments: DataListProps;
    trashedCount: any;
    filters: DataFilters;
    auth: AuthObject;
    errors: object;
}>();
const can = props?.auth?.can;
</script>

<template>
    <Head :title="$tChoice('trans.department', 2)" />
    <PageContainer :breadcrumbs="breadcrumbs">
        <DataTable
            :data="departments.data"
            :trashed-count="trashedCount"
            :filters="filters"
            :search-url="route('departments.index')"
            :pagination="{ ...departments.links, ...departments.meta }"
            :columns="createDepartmentColumns()"
            :on-create="() => onOpenModal(can['create:institution-settings'])"
            :disable-create="!can['create:institution-settings']"
        />
        <CreateEdit />
    </PageContainer>
</template>
