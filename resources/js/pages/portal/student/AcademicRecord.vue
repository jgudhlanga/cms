<script setup lang="ts">
import PageContainer from '@/components/core/page/PageContainer.vue';
import DataTable from '@/components/core/table/DataTable.vue';
import AcademicRecordForm from '@/components/students/academicRecords/AcademicRecordForm.vue';
import { useAcademicRecords } from '@/composables/students/useAcademicRecords';
import { AuthObject } from '@/types/data-pagination';
import { AcademicRecord } from '@/types/students';
import { BreadcrumbItemInterface } from '@/types/ui';
import { Head } from '@inertiajs/vue3';

interface Props {
    academicRecord: AcademicRecord[];
    auth: AuthObject;
    errors: object;
}

const props = defineProps<Props>();
const { user } = props.auth;
const breadcrumbs: BreadcrumbItemInterface[] = [{ title: user.attributes?.name }, { transKey: 'academic_record' }];
const { createAcademicRecordColumns, onOpenModal, allowed } = useAcademicRecords();
</script>
<template>
    <Head :title="$t('trans.academic_record')" />
    <PageContainer :breadcrumbs="breadcrumbs">
        <DataTable
            :data="academicRecord"
            :show-archived-filter="false"
            :columns="createAcademicRecordColumns()"
            :on-create="() => onOpenModal()"
            :disable-create="!allowed"
        />
        <AcademicRecordForm />
    </PageContainer>
</template>
