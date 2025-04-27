<script setup lang="ts">
import { onMounted, ref, watch } from 'vue';
import { CreateButton, ExportButton, ImportButton } from '@/components/core/button';
import { DataFilters, PaginationMeta, PaginationRootLink } from '@/types/data-pagination';
import { useDataTables } from '@/composables/core/useDataTables';
import { Archived, ColumnFilter, Paginator, Search, PerPageSize, TableHead, TableBody, GotoPage } from './';

interface Props {
	data: Array<any>,
	columns: Array<any>,
	filters?: DataFilters,
	searchUrl?: string,
	trashedCount?: any,
	pagination?: PaginationRootLink & PaginationMeta,
	onCreate?: () => void,
	onImport?: () => void,
	onExport?: () => void,
	disableCreate?: boolean,
	disableImport?: boolean,
	disableExport?: boolean,
	showArchivedFilter?: boolean,
}
const props = withDefaults(defineProps<Props>(), {
	disableCreate: false,
	disableImport: false,
	disableExport: false,
	showArchivedFilter: true
});

const filter = ref(props?.filters?.search ?? '');
const trashed = ref(props?.filters?.trashed ?? '0');
const pageSize = ref(props?.pagination?.per_page ?? '10');
const currentPage = ref(props?.pagination?.current_page ?? '1');
const { initialize, toggleColumnVisibility, tableSearch, setPageSize, goToPage, loadTrashed } = useDataTables();
const table = initialize(props);
const searchWatcher = tableSearch(pageSize, currentPage, trashed, props.searchUrl);
const pageSizeWatcher = setPageSize(filter, table, currentPage, trashed, props.searchUrl);
const goToPageWatcher = goToPage(filter, pageSize, trashed, props.searchUrl);
const trashedWatcher = loadTrashed(filter, pageSize, currentPage, props.searchUrl);

onMounted(() => {
	table.setPageSize(+pageSize.value);
});

const handleArchived = (archived: any) => {
	trashed.value = archived;
};
watch(filter, searchWatcher);
watch(pageSize, pageSizeWatcher);
watch(currentPage, goToPageWatcher);
watch(trashed, trashedWatcher);
</script>

<template>
	<div
		class="overflow-auto inline-block min-w-full align-middle rounded-xl pt-4 px-6 pb-6 bg-sidebar">
		<div class="flex w-full justify-between mb-6 mt-3 text-sm text-sidebar-foreground">
			<div class="flex w-full items-center space-x-3 ">
				<Search v-model="filter" v-if="searchUrl" />
				<Archived
					v-if="showArchivedFilter"
					:handle-archived="handleArchived"
					:trashed="+trashed"
					:trashed-count="trashedCount" />
				<slot name="head-left" />
			</div>
			<div class="flex w-1/2 items-center justify-end space-x-3">
				<ColumnFilter :table="table" :toggleColumnVisibility="toggleColumnVisibility" />
				<ExportButton class="rounded-full" v-if="onExport" @click="() => onExport ? onExport() : null"
				              :disable="disableExport" />
				<ImportButton class="rounded-full" v-if="onImport" @click="() => onImport ? onImport() : null"
				              :disable="disableImport" />
				<CreateButton class="rounded-full" v-if="onCreate" @click="() => onCreate ? onCreate() : null"
				              :disable="disableCreate" />
				<slot name="head-right" />
			</div>
		</div>
		<table class="hava-table">
			<TableHead :table="table" />
			<TableBody :table="table" />
		</table>
	</div>
	<div class="flex justify-between items-center px-6" v-if="pagination">
		<div class="flex w-full items-center space-x-3">
			<PerPageSize v-model="pageSize" />
			<GotoPage v-model="currentPage" :meta="pagination ?? null" />
		</div>
		<Paginator :meta="pagination" />
	</div>
</template>
