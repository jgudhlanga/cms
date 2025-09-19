<script setup lang="ts">
import PageContainer from '@/components/core/page/PageContainer.vue';
import DataTable from '@/components/core/table/DataTable.vue';
import { useStudentApplications } from '@/composables/students/useStudentApplications';
import { AuthObject } from '@/types/data-pagination';
import { Student, StudentProgram } from '@/types/students';
import { BreadcrumbItemInterface } from '@/types/ui';
import { Head } from '@inertiajs/vue3';

interface Props {
    auth: AuthObject;
    errors: object;
    student: Student;
    applications: StudentProgram[];
}

const props = defineProps<Props>();

const breadcrumbs: BreadcrumbItemInterface[] = [{ transChoiceKey: 'dashboard', href: route('portal.dashboard') }, { transChoiceKey: 'application' }];
const { createStudentApplicationColumns, allowed } = useStudentApplications();
</script>
<template>
    <Head :title="$tChoice('trans.application', 2)" />
    <PageContainer :breadcrumbs="breadcrumbs">
        <DataTable
            :data="applications"
            :show-archived-filter="false"
            :columns="createStudentApplicationColumns()"
            :on-create="() => {}"
            :disable-create="!allowed"
        />
    </PageContainer>
</template>
