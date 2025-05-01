<script setup lang="ts">
import { Head } from '@inertiajs/vue3';

import PageContainer from '@/components/core/page/PageContainer.vue';
import DataTable from '@/components/core/table/DataTable.vue';
import { useModeOfStudy } from '@/composables/institution/useModeOfStudy';
import { AuthObject, DataFilters, DataListProps } from '@/types/data-pagination';
import CreateEdit from './partials/CreateEdit.vue';

const { createModeOfStudyColumns, breadcrumbs, onOpenModal } = useModeOfStudy();

const props = defineProps<{
    modesOfStudy: DataListProps;
    trashedCount: any;
    filters: DataFilters;
    auth: AuthObject;
    errors: object;
}>();
const can = props?.auth?.can;
</script>

<template>
    <Head :title="$tChoice('trans.mode_of_study', 2)" />
    <PageContainer :breadcrumbs="breadcrumbs">
        <DataTable
            :data="modesOfStudy.data"
            :trashed-count="trashedCount"
            :filters="filters"
            :search-url="route('mode-of-studies.index')"
            :pagination="{ ...modesOfStudy.links, ...modesOfStudy.meta }"
            :columns="createModeOfStudyColumns()"
            :on-create="() => onOpenModal(can['create:institution-settings'])"
            :disable-create="!can['create:institution-settings']"
        />
        <CreateEdit />
    </PageContainer>
</template>
