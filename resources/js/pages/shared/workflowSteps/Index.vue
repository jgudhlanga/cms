<script setup lang="ts">
import { Head } from '@inertiajs/vue3';

import PageContainer from '@/components/core/page/PageContainer.vue';
import DataTable from '@/components/core/table/DataTable.vue';
import { useWorkflowSteps } from '@/composables/shared/useWorkflowSteps';
import { hasAbility } from '@/lib/permissions';
import { AuthObject, DataFilters, DataListProps } from '@/types/data-pagination';
import CreateEdit from './partials/CreateEdit.vue';

const { createWorkflowStepColumns, breadcrumbs, onOpenModal } = useWorkflowSteps();

defineProps<{
    workflowSteps: DataListProps;
    trashedCount: any;
    filters: DataFilters;
    auth: AuthObject;
    errors: object;
}>();
const allowed = hasAbility('create:settings');
</script>

<template>
    <Head :title="$tChoice('trans.workflow_step', 2)" />
    <PageContainer :breadcrumbs="breadcrumbs">
        <DataTable
            :data="workflowSteps.data"
            :trashed-count="trashedCount"
            :filters="filters"
            :search-url="route('workflow-steps.index')"
            :pagination="{ ...workflowSteps.links, ...workflowSteps.meta }"
            :columns="createWorkflowStepColumns()"
            :on-create="() => onOpenModal(allowed)"
            :disable-create="!allowed"
            :drag-items="true"
            draggable-update-url="workflow-steps.move-position"
        />
        <CreateEdit />
    </PageContainer>
</template>
