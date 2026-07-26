<script setup lang="ts">
import { Head } from '@inertiajs/vue3';

import PageContainer from '@/components/core/page/PageContainer.vue';
import DataTable from '@/components/core/table/DataTable.vue';
import { useSemesters } from '@/composables/academicCalendars/useSemesters';
import { hasAbility } from '@/lib/permissions';
import { AuthObject, DataFilters, DataListProps } from '@/types/data-pagination';
import CreateEdit from './partials/CreateEdit.vue';

const { createColumns, breadcrumbs, onOpenModal } = useSemesters();

defineProps<{
    semesters: DataListProps;
    trashedCount: any;
    filters: DataFilters;
    auth: AuthObject;
    errors: object;
}>();

const allowed = hasAbility('create:semesters');
</script>

<template>
    <Head :title="$tChoice('academic_years.semester', 2)" />
    <PageContainer :breadcrumbs="breadcrumbs">
        <DataTable
            :data="semesters.data"
            :trashed-count="trashedCount"
            :filters="filters"
            :search-url="route('semesters.index')"
            :pagination="{ ...semesters.links, ...semesters.meta }"
            :columns="createColumns()"
            :on-create="() => onOpenModal(allowed)"
            :disable-create="!allowed"
        />
        <CreateEdit />
    </PageContainer>
</template>
