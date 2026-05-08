<script setup lang="ts">
import { GenericButton } from '@/components/core/button';
import PageContainer from '@/components/core/page/PageContainer.vue';
import DataTable from '@/components/core/table/DataTable.vue';
import { useRolePermissions } from '@/composables/acl/useRolePermissions';
import { forbiddenAlert } from '@/lib/alerts';
import { getIdParams } from '@/lib/utils';
import AddEditRolePermissions from '@/pages/acl/roles/partials/AddEditRolePermissions.vue';
import RoleShowStats from '@/pages/acl/roles/partials/RoleShowStats.vue';
import { Permission, Role } from '@/types/acl';
import { AuthObject, DataFilters, DataListProps } from '@/types/data-pagination';
import { Head } from '@inertiajs/vue3';

const props = defineProps<{
    role: Role;
    permissions: DataListProps;
    allPermissions: Array<Permission>;
    filters: DataFilters;
    auth: AuthObject;
    errors: object;
}>();

const { createRolePermissionColumns, onOpenModal, showBreadcrumbs } = useRolePermissions(props.role);
const can = props?.auth?.can;
const breadcrumbs = [...showBreadcrumbs, ...[{ title: props.role?.attributes?.name }]];
</script>

<template>
    <Head :title="$tChoice('trans.role', 2)" />
    <PageContainer :breadcrumbs="breadcrumbs">
        <DataTable
            :data="permissions.data"
            :filters="filters"
            :show-archived-filter="false"
            :search-url="route('roles.show', getIdParams(role?.id?.toString() as string))"
            :pagination="{ ...permissions.links, ...permissions.meta }"
            :columns="createRolePermissionColumns()"
        >
            <template #head-left>
                <RoleShowStats :role="role" />
            </template>
            <template #head-right>
                <GenericButton
                    @click="() => (can['create:roles'] ? onOpenModal(role, permissions.data) : forbiddenAlert())"
                    :title="$t('trans.edit_role_permissions')"
                    class="rounded-full"
                />
            </template>
        </DataTable>
        <AddEditRolePermissions :role="role" :permissions="allPermissions" />
    </PageContainer>
</template>
