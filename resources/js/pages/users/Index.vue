<script setup lang="ts">
import PageContainer from '@/components/core/page/PageContainer.vue';
import DataTable from '@/components/core/table/DataTable.vue';
import { useUsers } from '@/composables/users/useUsers';
import { AuthObject, DataFilters, DataListProps } from '@/types/data-pagination';
import { Head } from '@inertiajs/vue3';

const { createUserColumns, breadcrumbs } = useUsers();

const props = defineProps<{
	users: DataListProps;
	trashedCount: any;
	filters: DataFilters;
	auth: AuthObject;
	errors: object;
}>();
const can = props?.auth?.can;
</script>

<template>
	<Head :title="$tChoice('trans.user', 2)" />
	<PageContainer :breadcrumbs="breadcrumbs">
		<DataTable
			:data="users.data"
			:trashed-count="trashedCount"
			:filters="filters"
			:search-url="route('users.index')"
			:pagination="{ ...users.links, ...users.meta }"
			:columns="createUserColumns()"
			:on-create="() => {}"
			:disable-create="!can['create:users']"
		/>
	</PageContainer>
</template>
