<script setup lang="ts">
import { Head } from '@inertiajs/vue3';

import PageContainer from '@/components/core/page/PageContainer.vue';
import DataTable from '@/components/core/table/DataTable.vue';
import { useWorkflowStepActions } from '@/composables/shared/useWorkflowStepActions';
import { hasAbility } from '@/lib/permissions';
import { AuthObject, DataFilters, DataListProps } from '@/types/data-pagination';
import CreateEdit from './partials/CreateEdit.vue';

const { createWorkflowStepActionColumns, breadcrumbs, onOpenModal } = useWorkflowStepActions();

defineProps<{
    workflowStepActions: DataListProps;
    trashedCount: any;
    filters: DataFilters;
    auth: AuthObject;
    errors: object;
}>();
const allowed = hasAbility('create:settings');
</script>

<template>
    <Head :title="$tChoice('trans.workflow_step_action', 2)" />
    <PageContainer :breadcrumbs="breadcrumbs">
        <DataTable
            :data="workflowStepActions.data"
            :trashed-count="trashedCount"
            :filters="filters"
            :search-url="route('workflow-step-actions.index')"
            :pagination="{ ...workflowStepActions.links, ...workflowStepActions.meta }"
            :columns="createWorkflowStepActionColumns()"
            :on-create="() => onOpenModal(allowed)"
            :disable-create="!allowed"
        />
        <CreateEdit />
    </PageContainer>
</template>
