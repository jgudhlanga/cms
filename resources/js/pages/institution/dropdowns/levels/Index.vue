<script setup lang="ts">
import { Head } from '@inertiajs/vue3';

import PageContainer from '@/components/core/page/PageContainer.vue';
import DataTable from '@/components/core/table/DataTable.vue';
import { useLevels } from '@/composables/institution/useLevels';
import { hasAbility } from '@/lib/permissions';
import { AuthObject, DataFilters, DataListProps } from '@/types/data-pagination';
import CreateEdit from './partials/CreateEdit.vue';

const { createLevelColumns, breadcrumbs, onOpenModal } = useLevels();
interface Props {
    levels: DataListProps;
    trashedCount: any;
    filters: DataFilters;
    auth: AuthObject;
    errors: object;
}

defineProps<Props>();

const allowed = hasAbility('create:institution-settings');
</script>

<template>
    <Head :title="$tChoice('trans.level', 2)" />
    <PageContainer :breadcrumbs="breadcrumbs">
        <DataTable
            :data="levels.data"
            :trashed-count="trashedCount"
            :filters="filters"
            :search-url="route('levels.index')"
            :pagination="{ ...levels.links, ...levels.meta }"
            :columns="createLevelColumns()"
            :on-create="() => onOpenModal(allowed)"
            :disable-create="!allowed"
            :drag-items="true"
            draggable-update-url="levels.move-position"
        />
        <CreateEdit />
    </PageContainer>
</template>
