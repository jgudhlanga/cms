<script setup lang="ts">
import {Head} from '@inertiajs/vue3';

import {AuthObject, DataFilters, DataListProps} from "@/types/data-pagination"
import { useRaces } from '@/composables/shared/useRaces';
import PageContainer from '@/components/core/page/PageContainer.vue';
import DataTable from '@/components/core/table/DataTable.vue';
import CreateEdit from './partials/CreateEdit.vue';

const {createRaceColumns, breadcrumbs, onOpenModal} = useRaces();

const props = defineProps<{
	races: DataListProps,
	trashedCount: any,
	filters: DataFilters,
	auth: AuthObject,
	errors: Object
}>();
const can = props?.auth?.can;
</script>

<template>
	<Head :title="$tChoice('trans.race', 2)"/>
	<PageContainer :breadcrumbs="breadcrumbs">
		<DataTable
			:data="races.data"
			:trashed-count="trashedCount"
			:filters="filters"
			:search-url="route('races.index')"
			:pagination="{...races.links, ...races.meta}"
			:columns="createRaceColumns()"
			:on-create="() => onOpenModal(can['create:settings']) "
			:disable-create="!can['create:settings']"
		/>
		<CreateEdit/>
	</PageContainer>
</template>
