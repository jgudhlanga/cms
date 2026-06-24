<script setup lang="ts">
import PageContainer from '@/components/core/page/PageContainer.vue';
import DataTable from '@/components/core/table/DataTable.vue';
import { useStudentApplications } from '@/composables/students/useStudentApplications';
import { AuthObject } from '@/types/data-pagination';
import { StudentApplication } from '@/types/students';
import { BreadcrumbItemInterface } from '@/types/ui';
import { Head } from '@inertiajs/vue3';

interface Props {
    programs: StudentApplication[];
    auth: AuthObject;
    errors: object;
}

const props = defineProps<Props>();
const { user } = props.auth;
const breadcrumbs: BreadcrumbItemInterface[] = [{ title: user.attributes?.name }, { transChoiceKey: 'program' }];
const { createStudentApplicationColumns, allowed } = useStudentApplications();
</script>
<template>
    <Head :title="$tChoice('trans.program', 2)" />
    <PageContainer :breadcrumbs="breadcrumbs">
        <DataTable
            :data="programs"
            :show-archived-filter="false"
            :columns="createStudentApplicationColumns()"
            :on-create="() => {}"
            :disable-create="!allowed"
        />
    </PageContainer>
</template>
