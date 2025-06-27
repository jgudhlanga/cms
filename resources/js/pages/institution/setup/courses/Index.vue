<script setup lang="ts">
import { Head } from '@inertiajs/vue3';

import PageContainer from '@/components/core/page/PageContainer.vue';
import DataTable from '@/components/core/table/DataTable.vue';
import { useCourses } from '@/composables/institution/useCourses';
import { hasAbility } from '@/lib/permissions';
import { AuthObject, DataFilters, DataListProps } from '@/types/data-pagination';
import CreateEdit from './partials/CreateEdit.vue';

const { createCourseColumns, breadcrumbs, onOpenModal } = useCourses();

defineProps<{
    courses: DataListProps;
    trashedCount: any;
    filters: DataFilters;
    auth: AuthObject;
    errors: object;
}>();
const allowed = hasAbility('create:institution-settings');
</script>

<template>
    <Head :title="$tChoice('trans.course', 2)" />
    <PageContainer :breadcrumbs="breadcrumbs">
        <DataTable
            :data="courses.data"
            :trashed-count="trashedCount"
            :filters="filters"
            :search-url="route('courses.index')"
            :pagination="{ ...courses.links, ...courses.meta }"
            :columns="createCourseColumns()"
            :on-create="() => onOpenModal(allowed)"
            :disable-create="!allowed"
            :drag-items="true"
            draggable-update-url="courses.move-position"
        />
        <CreateEdit />
    </PageContainer>
</template>
