<script setup lang="ts">
import { Head } from '@inertiajs/vue3';

import PageContainer from '@/components/core/page/PageContainer.vue';
import DataTable from '@/components/core/table/DataTable.vue';
import { useApplicationSteps } from '@/composables/shared/useApplicationSteps';
import { hasAbility } from '@/lib/permissions';
import { AuthObject, DataFilters, DataListProps } from '@/types/data-pagination';
import CreateEdit from './partials/CreateEdit.vue';

const { createApplicationStepColumns, breadcrumbs, onOpenModal } = useApplicationSteps();

defineProps<{
    applicationSteps: DataListProps;
    trashedCount: any;
    filters: DataFilters;
    auth: AuthObject;
    errors: object;
}>();
const allowed = hasAbility('create:settings');
</script>

<template>
    <Head :title="$tChoice('trans.application_step', 2)" />
    <PageContainer :breadcrumbs="breadcrumbs">
        <DataTable
            :data="applicationSteps.data"
            :trashed-count="trashedCount"
            :filters="filters"
            :search-url="route('application-steps.index')"
            :pagination="{ ...applicationSteps.links, ...applicationSteps.meta }"
            :columns="createApplicationStepColumns()"
            :on-create="() => onOpenModal(allowed)"
            :disable-create="!allowed"
            :drag-items="true"
            draggable-update-url="application-steps.move-position"
        />
        <CreateEdit />
    </PageContainer>
</template>
