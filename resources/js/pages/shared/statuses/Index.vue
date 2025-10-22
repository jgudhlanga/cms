<script setup lang="ts">
import {Head} from '@inertiajs/vue3';

import {AuthObject, DataFilters, DataListProps} from "@/types/data-pagination"
import { useStatuses } from '@/composables/shared/useStatuses';
import PageContainer from '@/components/core/page/PageContainer.vue';
import DataTable from '@/components/core/table/DataTable.vue';
import CreateEdit from './partials/CreateEdit.vue';

const {createStatusColumns, breadcrumbs, onOpenModal} = useStatuses();

const props = defineProps<{
	statuses: DataListProps,
	trashedCount: any,
	filters: DataFilters,
	auth: AuthObject,
	errors: object
}>();
const can = props?.auth?.can;
</script>

<template>
	<Head :title="$tChoice('trans.status', 2)"/>
	<PageContainer :breadcrumbs="breadcrumbs">
		<DataTable
			:data="statuses.data"
			:trashed-count="trashedCount"
			:filters="filters"
			:search-url="route('statuses.index')"
			:pagination="{...statuses.links, ...statuses.meta}"
			:columns="createStatusColumns()"
			:on-create="() => onOpenModal(can['create:settings']) "
			:disable-create="!can['create:settings']"
		/>
		<CreateEdit/>
	</PageContainer>
</template>
