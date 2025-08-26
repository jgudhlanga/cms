<script setup lang="ts">
import { Head } from '@inertiajs/vue3';

import PageContainer from '@/components/core/page/PageContainer.vue';
import DataTable from '@/components/core/table/DataTable.vue';
import { useSubjects } from '@/composables/institution/useSubjects';
import { AuthObject, DataFilters, DataListProps } from '@/types/data-pagination';
import CreateEdit from './partials/CreateEdit.vue';
import { hasAbility } from '@/lib/permissions';

const { createSubjectColumns, breadcrumbs, onOpenModal } = useSubjects();

 defineProps<{
    subjects: DataListProps;
    trashedCount: any;
    filters: DataFilters;
    auth: AuthObject;
    errors: object;
}>();
const allowed = hasAbility('create:institution-settings');
</script>

<template>
    <Head :title="$tChoice('trans.subject', 2)" />
    <PageContainer :breadcrumbs="breadcrumbs">
        <DataTable
            :data="subjects.data"
            :trashed-count="trashedCount"
            :filters="filters"
            :search-url="route('subjects.index')"
            :pagination="{ ...subjects.links, ...subjects.meta }"
            :columns="createSubjectColumns()"
            :on-create="() => onOpenModal(allowed)"
            :disable-create="!allowed"
            :drag-items="true"
            draggable-update-url="subjects.move-position"
        />
        <CreateEdit />
    </PageContainer>
</template>
