<script setup lang="ts">
import { Head } from '@inertiajs/vue3';

import PageContainer from '@/components/core/page/PageContainer.vue';
import DataTable from '@/components/core/table/DataTable.vue';
import { useStudentEnrolmentStatuses } from '@/composables/students/useStudentEnrolmentStatuses';
import { hasAbility } from '@/lib/permissions';
import { AuthObject, DataFilters, DataListProps } from '@/types/data-pagination';
import CreateEdit from './partials/CreateEdit.vue';

const { createColumns, breadcrumbs, onOpenModal } = useStudentEnrolmentStatuses();

defineProps<{
    studentEnrolmentStatuses: DataListProps;
    trashedCount: any;
    filters: DataFilters;
    auth: AuthObject;
    errors: object;
}>();
const allowed = hasAbility('create:settings');
</script>

<template>
    <Head :title="$tChoice('students.enrolment_status', 2)" />
    <PageContainer :breadcrumbs="breadcrumbs">
        <DataTable
            :data="studentEnrolmentStatuses.data"
            :trashed-count="trashedCount"
            :filters="filters"
            :search-url="route('student-enrolment-statuses.index')"
            :pagination="{ ...studentEnrolmentStatuses.links, ...studentEnrolmentStatuses.meta }"
            :columns="createColumns()"
            :on-create="() => onOpenModal(allowed)"
            :disable-create="!allowed"
        />
        <CreateEdit />
    </PageContainer>
</template>
