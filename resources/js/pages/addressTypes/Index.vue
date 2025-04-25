<script setup lang="ts">
import {Head} from '@inertiajs/vue3';

import {AuthObject, DataFilters, DataListProps} from "@/types/data-pagination"
import PageContainer from '@/components/core/page/PageContainer.vue';
import DataTable from '@/components/core/table/DataTable.vue';
import CreateEdit from './partials/CreateEdit.vue';
import { useAddressTypes } from '@/composables/addressTypes/useAddressTypes';
import { Auth } from '@/types';

const {createAddressTypeColumns, breadcrumbs, onOpenModal} = useAddressTypes();

interface Props {
	addressTypes: DataListProps,
	trashedCount: any,
	filters: DataFilters,
	auth: AuthObject,
	errors: Object
}
const props = defineProps<Props>();

const {can} = props?.auth as Auth;
</script>

<template>
	<Head :title="$tChoice('trans.address_type', 2)"/>
	<PageContainer :breadcrumbs="breadcrumbs">
		<DataTable
			:data="addressTypes.data"
			:trashed-count="trashedCount"
			:filters="filters"
			:search-url="route('address-types.index')"
			:pagination="{...addressTypes.links, ...addressTypes.meta}"
			:columns="createAddressTypeColumns()"
			:on-create="() => onOpenModal(can['create:settings']) "
			:disable-create="!can['create:settings']"
		/>
		<CreateEdit/>
	</PageContainer>
</template>
