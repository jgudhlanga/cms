<script setup lang="ts">
import { Head } from '@inertiajs/vue3';

import PageContainer from '@/components/core/page/PageContainer.vue';
import DataTable from '@/components/core/table/DataTable.vue';
import { useGrades } from '@/composables/institution/useGrades';
import { hasAbility } from '@/lib/permissions';
import { AuthObject, DataFilters, DataListProps } from '@/types/data-pagination';
import CreateEdit from './partials/CreateEdit.vue';

const { createGradeColumns, breadcrumbs, onOpenModal } = useGrades();

defineProps<{
    grades: DataListProps;
    trashedCount: any;
    filters: DataFilters;
    auth: AuthObject;
    errors: object;
}>();
const allowed = hasAbility('create:institution-settings');
</script>

<template>
    <Head :title="$tChoice('trans.grade', 2)" />
    <PageContainer :breadcrumbs="breadcrumbs">
        <DataTable
            :data="grades.data"
            :trashed-count="trashedCount"
            :filters="filters"
            :search-url="route('grades.index')"
            :pagination="{ ...grades.links, ...grades.meta }"
            :columns="createGradeColumns()"
            :on-create="() => onOpenModal(allowed)"
            :disable-create="!allowed"
            :drag-items="true"
            draggable-update-url="grades.move-position"
        />
        <CreateEdit />
    </PageContainer>
</template>
