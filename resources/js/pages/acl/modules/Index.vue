<script setup lang="ts">
import PageContainer from '@/components/core/page/PageContainer.vue';
import DataTable from '@/components/core/table/DataTable.vue';
import { useModules } from '@/composables/acl/useModules';
import CreateEdit from '@/pages/acl/modules/partials/CreateEdit.vue';
import { AuthObject, DataFilters, DataListProps } from '@/types/data-pagination';
import { Head } from '@inertiajs/vue3';

const { createModuleColumns, breadcrumbs, onOpenModal } = useModules();
const props = defineProps<{
    modules: DataListProps;
    trashedCount: any;
    filters: DataFilters;
    auth: AuthObject;
    errors: object;
}>();
const can = props?.auth?.can;
</script>

<template>
    <Head :title="$tChoice('trans.module', 2)" />
    <PageContainer :breadcrumbs="breadcrumbs">
        <DataTable
            :data="modules.data"
            :trashed-count="trashedCount"
            :filters="filters"
            :search-url="route('modules.index')"
            :pagination="{ ...modules.links, ...modules.meta }"
            :columns="createModuleColumns()"
            :on-create="() => onOpenModal(can['create:modules'])"
            :disable-create="!can['create:modules']"
        />
        <CreateEdit />
    </PageContainer>
</template>
