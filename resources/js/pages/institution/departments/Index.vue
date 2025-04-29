<script setup lang="ts">
import { Head } from '@inertiajs/vue3';

import PageContainer from '@/components/core/page/PageContainer.vue';
import DataTable from '@/components/core/table/DataTable.vue';
import { useInstitutionDepartments } from '@/composables/institution/useInstitutionDepartments';
import { AuthObject, DataFilters, DataListProps } from '@/types/data-pagination';

const { createInstitutionDepartmentColumns, breadcrumbs, onOpenModal } = useInstitutionDepartments();

const props = defineProps<{
    institutionDepartments: DataListProps;
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
            :data="institutionDepartments.data"
            :trashed-count="trashedCount"
            :filters="filters"
            :search-url="route('institution-departments.index')"
            :pagination="{ ...institutionDepartments.links, ...institutionDepartments.meta }"
            :columns="createInstitutionDepartmentColumns()"
            :on-create="() => onOpenModal(can['create:institution-departments'])"
            :disable-create="!can['create:institution-departments']"
        />
    </PageContainer>
</template>
