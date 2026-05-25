<script setup lang="ts">
import { Head } from '@inertiajs/vue3';

import PageContainer from '@/components/core/page/PageContainer.vue';
import DataTable from '@/components/core/table/DataTable.vue';
import { useAssessmentTypes } from '@/composables/institution/useAssessmentTypes';
import { AuthObject, DataFilters, DataListProps } from '@/types/data-pagination';
import CreateEdit from './partials/CreateEdit.vue';

const { createAssessmentTypeColumns, breadcrumbs, onOpenModal } = useAssessmentTypes();

const props = defineProps<{
    assessmentTypes: DataListProps;
    modesOfStudy: Array<{ id: number; name: string }>;
    trashedCount: number;
    filters: DataFilters;
    auth: AuthObject;
    errors: object;
}>();
const can = props?.auth?.can;
</script>

<template>
    <Head :title="$tChoice('trans.assessment_type', 2)" />
    <PageContainer :breadcrumbs="breadcrumbs">
        <DataTable
            :data="assessmentTypes.data"
            :trashed-count="trashedCount"
            :filters="filters"
            :search-url="route('assessment-types.index')"
            :pagination="{ ...assessmentTypes.links, ...assessmentTypes.meta }"
            :columns="createAssessmentTypeColumns()"
            :on-create="() => onOpenModal(can['create:institution-settings'])"
            :disable-create="!can['create:institution-settings']"
        />
        <CreateEdit :modes-of-study="modesOfStudy" />
    </PageContainer>
</template>
