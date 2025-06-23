<script setup lang="ts">
import { Head } from '@inertiajs/vue3';

import PageContainer from '@/components/core/page/PageContainer.vue';
import DataTable from '@/components/core/table/DataTable.vue';
import { useAcademicLevels } from '@/composables/academicLevels/useAcademicLevels';
import { AuthObject, DataFilters, DataListProps } from '@/types/data-pagination';
import CreateEdit from './partials/CreateEdit.vue';

const { createAcademicLevelColumns, breadcrumbs, onOpenModal } = useAcademicLevels();

const props = defineProps<{
    academicLevels: DataListProps;
    trashedCount: any;
    filters: DataFilters;
    auth: AuthObject;
    errors: object;
}>();
const can = props?.auth?.can;
</script>

<template>
    <Head :title="$tChoice('trans.level', 2)" />
    <PageContainer :breadcrumbs="breadcrumbs">
        <DataTable
            :data="academicLevels.data"
            :trashed-count="trashedCount"
            :filters="filters"
            :search-url="route('academic-levels.index')"
            :pagination="{ ...academicLevels.links, ...academicLevels.meta }"
            :columns="createAcademicLevelColumns()"
            :on-create="() => onOpenModal(can['create:settings'])"
            :disable-create="!can['create:settings']"
        />
        <CreateEdit />
    </PageContainer>
</template>
