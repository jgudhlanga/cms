<script setup lang="ts">
import { Head } from '@inertiajs/vue3';

import PageContainer from '@/components/core/page/PageContainer.vue';
import DataTable from '@/components/core/table/DataTable.vue';
import { useAcademicYearOptions } from '@/composables/academicCalendars/useAcademicYearOptions';
import { hasAbility } from '@/lib/permissions';
import { AuthObject, DataFilters, DataListProps } from '@/types/data-pagination';
import CreateEdit from './partials/CreateEdit.vue';

const { createColumns, breadcrumbs, onOpenModal } = useAcademicYearOptions();

defineProps<{
    academicYearOptions: DataListProps;
    trashedCount: any;
    filters: DataFilters;
    auth: AuthObject;
    errors: object;
}>();

const allowed = hasAbility('create:settings');
</script>

<template>
    <Head :title="$tChoice('academic_years.calendar_year_option', 2)" />
    <PageContainer :breadcrumbs="breadcrumbs">
        <DataTable
            :data="academicYearOptions.data"
            :trashed-count="trashedCount"
            :filters="filters"
            :search-url="route('academic-year-options.index')"
            :pagination="{ ...academicYearOptions.links, ...academicYearOptions.meta }"
            :columns="createColumns()"
            :on-create="() => onOpenModal(allowed)"
            :disable-create="!allowed"
        />
        <CreateEdit />
    </PageContainer>
</template>
