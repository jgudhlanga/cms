<script setup lang="ts">
import {Head} from '@inertiajs/vue3';

import {AuthObject, DataFilters, DataListProps} from "@/types/data-pagination"
import { useProvinces } from '@/composables/shared/useProvinces';
import PageContainer from '@/components/core/page/PageContainer.vue';
import DataTable from '@/components/core/table/DataTable.vue';
import CreateEdit from './partials/CreateEdit.vue';

const {createProvinceColumns, breadcrumbs, onOpenModal} = useProvinces();

const props = defineProps<{
	provinces: DataListProps,
	trashedCount: any,
	filters: DataFilters,
	auth: AuthObject,
	errors: object
}>();
const can = props?.auth?.can;
</script>

<template>
	<Head :title="$tChoice('trans.province', 2)"/>
	<PageContainer :breadcrumbs="breadcrumbs">
		<DataTable
			:data="provinces.data"
			:trashed-count="trashedCount"
			:filters="filters"
			:search-url="route('provinces.index')"
			:pagination="{...provinces.links, ...provinces.meta}"
			:columns="createProvinceColumns()"
			:on-create="() => onOpenModal(can['create:settings']) "
			:disable-create="!can['create:settings']"
		/>
		<CreateEdit/>
	</PageContainer>
</template> 
