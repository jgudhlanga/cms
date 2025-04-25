<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import PageContainer from '@/components/core/page/PageContainer.vue';
import DataTable from '@/components/core/table/DataTable.vue';
import { usePermissions } from '@/composables/acl/usePermissions';
import { AuthObject, DataFilters, DataListProps } from '@/types/data-pagination';
import CreateEdit from '@/pages/acl/permissions/partials/CreateEdit.vue';

const { createPermissionColumns, breadcrumbs, onOpenModal } = usePermissions();
const props = defineProps<{
	permissions: DataListProps;
	trashedCount: any;
	filters: DataFilters;
	auth: AuthObject;
	errors: object;
}>();
const can = props?.auth?.can;
</script>

<template>
	<Head :title="$tChoice('trans.permission', 2)" />
	<PageContainer :breadcrumbs="breadcrumbs">
		<DataTable
			:data="permissions.data"
			:trashed-count="trashedCount"
			:filters="filters"
			:search-url="route('permissions.index')"
			:pagination="{ ...permissions.links, ...permissions.meta }"
			:columns="createPermissionColumns()"
			:on-create="() => onOpenModal(can['create:permissions'])"
			:disable-create="!can['create:permissions']"
		/>
		<CreateEdit />
	</PageContainer>
</template>
