<script setup lang="ts">
import { Head } from '@inertiajs/vue3';

import PageContainer from '@/components/core/page/PageContainer.vue';
import DataTable from '@/components/core/table/DataTable.vue';
import { useEmploymentTypes } from '@/composables/shared/useEmploymentTypes';
import { hasAbility } from '@/lib/permissions';
import { AuthObject, DataFilters, DataListProps } from '@/types/data-pagination';
import CreateEdit from './partials/CreateEdit.vue';

const { createEmploymentTypeColumns, breadcrumbs, onOpenModal } = useEmploymentTypes();

defineProps<{
    employmentTypes: DataListProps;
    trashedCount: any;
    filters: DataFilters;
    auth: AuthObject;
    errors: object;
}>();
const allowed = hasAbility('create:settings');
</script>

<template>
    <Head :employmentType="$tChoice('trans.employment_type', 2)" />
    <PageContainer :breadcrumbs="breadcrumbs">
        <DataTable
            :data="employmentTypes.data"
            :trashed-count="trashedCount"
            :filters="filters"
            :search-url="route('employment-types.index')"
            :pagination="{ ...employmentTypes.links, ...employmentTypes.meta }"
            :columns="createEmploymentTypeColumns()"
            :on-create="() => onOpenModal(allowed)"
            :disable-create="!allowed"
        />
        <CreateEdit />
    </PageContainer>
</template>
