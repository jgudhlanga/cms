<script setup lang="ts">
import { Head } from '@inertiajs/vue3';

import PageContainer from '@/components/core/page/PageContainer.vue';
import DataTable from '@/components/core/table/DataTable.vue';
import { useStudents } from '@/composables/students/useStudents';
import { AuthObject, DataFilters, DataListProps } from '@/types/data-pagination';
import { Link } from '@/types/ui';

const { createStudentColumns } = useStudents();

interface Props {
    students: DataListProps;
    trashedCount: any;
    filters: DataFilters;
    auth: AuthObject;
    errors: object;
}

defineProps<Props>();
const breadcrumbs: Array<Link> = [{ transKey: 'dashboard', href: route('dashboard') }, { transChoiceKey: 'student' }];

const loadStudents = () => {};
</script>

<template>
    <Head :title="$tChoice('student', 2)" />
    <PageContainer :breadcrumbs="breadcrumbs">
        <DataTable
            :data="students.data"
            :filters="filters"
            :show-archived-filter="false"
            :pagination="{ ...students.links, ...students.meta }"
            :columns="createStudentColumns()"
        >
            <template #head-left>
                <StudentFilters :filters="filters" @change="loadStudents" />
            </template>
        </DataTable>
    </PageContainer>
</template>
