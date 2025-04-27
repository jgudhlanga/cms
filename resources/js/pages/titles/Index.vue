<script setup lang="ts">
import {Head} from '@inertiajs/vue3';

import {AuthObject, DataFilters, DataListProps} from "@/types/data-pagination"
import { useTitles } from '@/composables/titles/useTitles';
import PageContainer from '@/components/core/page/PageContainer.vue';
import DataTable from '@/components/core/table/DataTable.vue';
import CreateEdit from './partials/CreateEdit.vue';

const {createTitleColumns, breadcrumbs, onOpenModal} = useTitles();

const props = defineProps<{
	titles: DataListProps,
	trashedCount: any,
	filters: DataFilters,
	auth: AuthObject,
	errors: Object
}>();
const can = props?.auth?.can;
</script>

<template>
	<Head :title="$tChoice('trans.title', 2)"/>
	<PageContainer :breadcrumbs="breadcrumbs">
		<DataTable
			:data="titles.data"
			:trashed-count="trashedCount"
			:filters="filters"
			:search-url="route('titles.index')"
			:pagination="{...titles.links, ...titles.meta}"
			:columns="createTitleColumns()"
			:on-create="() => onOpenModal(can['create:settings']) "
			:disable-create="!can['create:settings']"
		/>
		<CreateEdit/>
	</PageContainer>
</template>
