<script setup lang="ts">
import { Head } from '@inertiajs/vue3';

import PageContainer from '@/components/core/page/PageContainer.vue';
import DataTable from '@/components/core/table/DataTable.vue';
import { useDivisions } from '@/composables/institution/useDivisions';
import { hasAbility } from '@/lib/permissions';
import { AuthObject, DataFilters, DataListProps } from '@/types/data-pagination';
import CreateEdit from './partials/CreateEdit.vue';

const { createDivisionColumns, breadcrumbs, onOpenModal } = useDivisions();

defineProps<{
    divisions: DataListProps;
    trashedCount: any;
    filters: DataFilters;
    auth: AuthObject;
    errors: object;
}>();
const allowed = hasAbility('create:institution-settings');
</script>

<template>
    <Head :title="$tChoice('trans.division', 2)" />
    <PageContainer :breadcrumbs="breadcrumbs">
        <DataTable
            :data="divisions.data"
            :trashed-count="trashedCount"
            :filters="filters"
            :search-url="route('divisions.index')"
            :pagination="{ ...divisions.links, ...divisions.meta }"
            :columns="createDivisionColumns()"
            :on-create="() => onOpenModal(allowed)"
            :disable-create="!allowed"
            :drag-items="true"
            draggable-update-url="divisions.move-position"
        />
        <CreateEdit />
    </PageContainer>
</template>
