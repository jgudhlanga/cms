<script setup lang="ts">
import PageContainer from '@/components/core/page/PageContainer.vue';
import DataTable from '@/components/core/table/DataTable.vue';
import { useRoles } from '@/composables/acl/useRoles';
import CreateEditRole from '@/pages/acl/roles/partials/CreateEditRole.vue';
import { AuthObject, DataFilters, DataListProps } from '@/types/data-pagination';
import { Head } from '@inertiajs/vue3';

const { createRoleColumns, indexBreadcrumbs, onOpenModal } = useRoles();

const props = defineProps<{
    roles: DataListProps;
    trashedCount: any;
    filters: DataFilters;
    auth: AuthObject;
    errors: object;
}>();
const can = props?.auth?.can;
</script>

<template>
    <Head :title="$tChoice('trans.role', 2)" />
    <PageContainer :breadcrumbs="indexBreadcrumbs">
        <DataTable
            :data="roles.data"
            :trashed-count="trashedCount"
            :filters="filters"
            :search-url="route('roles.index')"
            :pagination="{ ...roles.links, ...roles.meta }"
            :columns="createRoleColumns()"
            :on-create="() => onOpenModal(can['create:roles'])"
            :disable-create="!can['create:roles']"
        />
        <CreateEditRole />
    </PageContainer>
</template>
