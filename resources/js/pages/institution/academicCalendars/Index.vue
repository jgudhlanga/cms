<script setup lang="ts">
import { Head } from '@inertiajs/vue3';

import PageContainer from '@/components/core/page/PageContainer.vue';
import DataTable from '@/components/core/table/DataTable.vue';

import { useAcademicCalendars } from '@/composables/academicCalendars/useAcademicCalendars';
import { hasAbility } from '@/lib/permissions';
import { AcademicCalendar } from '@/types/academic-calendar';
import { AuthObject, DataFilters, DataListProps } from '@/types/data-pagination';
import CreateEdit from './partials/CreateEdit.vue';

const { createTableColumns, breadcrumbs, onOpenModal } = useAcademicCalendars();

defineProps<{
    academicCalendars: DataListProps<AcademicCalendar>;
    trashedCount: any;
    filters: DataFilters;
    auth: AuthObject;
    errors: object;
}>();
const allowed = hasAbility('create:academic-calendars');
</script>

<template>
    <Head :title="$tChoice('academic_calendar.academic_calendar', 2)" />
    <PageContainer :breadcrumbs="breadcrumbs">
        <DataTable
            :data="academicCalendars.data"
            :trashed-count="trashedCount"
            :filters="filters"
            :search-url="route('academic-calendars.index')"
            :pagination="{ ...academicCalendars.links, ...academicCalendars.meta }"
            :columns="createTableColumns()"
            :on-create="() => onOpenModal(allowed)"
            :disable-create="!allowed"
        />
        <CreateEdit />
    </PageContainer>
</template>
