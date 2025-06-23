<script setup lang="ts">
import { Head } from '@inertiajs/vue3';

import { AuthObject, DataFilters, DataListProps } from '@/types/data-pagination';
import { useCountries } from '@/composables/countries/useCountries';
import PageContainer from '@/components/core/page/PageContainer.vue';
import DataTable from '@/components/core/table/DataTable.vue';
import CreateEdit from './partials/CreateEdit.vue';

const { createCountryColumns, breadcrumbs, onOpenModal } = useCountries();

const props = defineProps<{
	countries: DataListProps,
	trashedCount: any,
	filters: DataFilters,
	auth: AuthObject,
	errors: object
}>();
const can = props?.auth?.can;
</script>

<template>
	<Head :title="$tChoice('trans.country', 2)" />
	<PageContainer :breadcrumbs="breadcrumbs">
		<DataTable
			:data="countries.data"
			:trashed-count="trashedCount"
			:filters="filters"
			:search-url="route('countries.index')"
			:pagination="{...countries.links, ...countries.meta}"
			:columns="createCountryColumns()"
			:on-create="() => onOpenModal(can['create:settings'])"
			:disable-create="!can['create:settings']"
		/>
		<CreateEdit />
	</PageContainer>
</template>
