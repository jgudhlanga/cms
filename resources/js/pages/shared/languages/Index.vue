<script setup lang="ts">
import {Head} from '@inertiajs/vue3';

import {AuthObject, DataFilters, DataListProps} from "@/types/data-pagination"
import { useLanguages } from '@/composables/languages/useLanguages';
import PageContainer from '@/components/core/page/PageContainer.vue';
import DataTable from '@/components/core/table/DataTable.vue';
import CreateEdit from './partials/CreateEdit.vue';

const {createLanguageColumns, breadcrumbs, onOpenModal} = useLanguages();

const props = defineProps<{
	languages: DataListProps,
	trashedCount: any,
	filters: DataFilters,
	auth: AuthObject,
	errors: Object
}>();
const can = props?.auth?.can;
</script>

<template>
	<Head :title="$tChoice('trans.language', 2)"/>
	<PageContainer :breadcrumbs="breadcrumbs">
		<DataTable
			:data="languages.data"
			:trashed-count="trashedCount"
			:filters="filters"
			:search-url="route('languages.index')"
			:pagination="{...languages.links, ...languages.meta}"
			:columns="createLanguageColumns()"
			:on-create="() => onOpenModal(can['create:settings']) "
			:disable-create="!can['create:settings']"
		/>
		<CreateEdit/>
	</PageContainer>
</template>
