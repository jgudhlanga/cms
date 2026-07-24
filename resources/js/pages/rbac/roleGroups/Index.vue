<script setup lang="ts">
import PageContainer from '@/components/core/page/PageContainer.vue';
import DataTable from '@/components/core/table/DataTable.vue';
import { useRoleGroups } from '@/composables/rbac/useRoleGroups';
import { hasAbility } from '@/lib/permissions';
import CreateEditRoleGroup from '@/pages/rbac/roleGroups/partials/CreateEditRoleGroup.vue';
import { AuthObject, DataFilters, DataListProps } from '@/types/data-pagination';
import { Head } from '@inertiajs/vue3';

const { createRoleGroupColumns, indexBreadcrumbs, onOpenModal } = useRoleGroups();

defineProps<{
    roleGroups: DataListProps;
    trashedCount: any;
    filters: DataFilters;
    auth: AuthObject;
    errors: object;
}>();
const allowed = hasAbility('create:settings');
</script>

<template>
    <Head :title="$tChoice('trans.role_group', 2)" />
    <PageContainer :breadcrumbs="indexBreadcrumbs">
        <DataTable
            :data="roleGroups.data"
            :trashed-count="trashedCount"
            :filters="filters"
            :search-url="route('role-groups.index')"
            :pagination="{ ...roleGroups.links, ...roleGroups.meta }"
            :columns="createRoleGroupColumns()"
            :on-create="() => onOpenModal(allowed)"
            :disable-create="!allowed"
        />
        <CreateEditRoleGroup />
    </PageContainer>
</template>
