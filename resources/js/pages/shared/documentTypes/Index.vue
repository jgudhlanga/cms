<script setup lang="ts">
import { Head } from '@inertiajs/vue3';

import PageContainer from '@/components/core/page/PageContainer.vue';
import DataTable from '@/components/core/table/DataTable.vue';
import { useDocumentTypes } from '@/composables/shared/useDocumentTypes';
import { hasAbility } from '@/lib/permissions';
import { AuthObject, DataFilters, DataListProps } from '@/types/data-pagination';
import CreateEdit from './partials/CreateEdit.vue';

const { createDocumentTypeColumns, breadcrumbs, onOpenModal } = useDocumentTypes();

defineProps<{
    documentTypes: DataListProps;
    trashedCount: any;
    filters: DataFilters;
    auth: AuthObject;
    errors: object;
}>();
const allowed = hasAbility('create:settings');
</script>

<template>
    <Head :idType="$tChoice('trans.document_type', 2)" />
    <PageContainer :breadcrumbs="breadcrumbs">
        <DataTable
            :data="documentTypes.data"
            :trashed-count="trashedCount"
            :filters="filters"
            :search-url="route('document-types.index')"
            :pagination="{ ...documentTypes.links, ...documentTypes.meta }"
            :columns="createDocumentTypeColumns()"
            :on-create="() => onOpenModal(allowed)"
            :disable-create="!allowed"
        />
        <CreateEdit />
    </PageContainer>
</template>
