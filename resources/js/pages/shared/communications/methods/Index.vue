<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import DataTable from '@/components/core/table/DataTable.vue';
import { AuthObject, DataFilters, DataListProps } from '@/types/data-pagination';
import PageContainer from '@/components/core/page/PageContainer.vue';
import CreateEdit from './partials/CreateEdit.vue';
import { useCommunicationMethods } from '@/composables/shared/useCommunicationMethods';

const { createMethodsColumns, breadcrumbs, onOpenModal } = useCommunicationMethods();

const props = defineProps<{
	methods: DataListProps,
	trashedCount: any,
	filters: DataFilters,
	auth: AuthObject,
	errors: Object
}>();
const can = props?.auth?.can;
</script>

<template>
	<Head :title="$tChoice('trans.communication_mode', 2)" />
	<PageContainer :breadcrumbs="breadcrumbs">
		<DataTable
			:data="methods.data"
			:trashed-count="trashedCount"
			:filters="filters"
			:search-url="route('communication-methods.index')"
			:pagination="{...methods.links, ...methods.meta}"
			:columns="createMethodsColumns()"
			:on-create="() => onOpenModal(can['create:settings'])"
			:disable-create="!can['create:settings']"
		/>
		<CreateEdit />
	</PageContainer>
</template>
