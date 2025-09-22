<script setup lang="ts">
import { CreateButton, ExportButton, ImportButton } from '@/components/core/button';
import { useDataTables } from '@/composables/core/useDataTables';
import { PAGINATION_ITEMS_PER_PAGE } from '@/lib/constants';
import { DataFilters, PaginationMeta, PaginationRootLink } from '@/types/data-pagination';
import { onMounted, ref, watch } from 'vue';
import { Archived, ColumnFilter, GotoPage, Paginator, PerPageSize, Search, TableBody, TableHead } from './';
import { ColorVariant } from '@/enums/colors';

interface Props {
    data: Array<any>;
    columns: Array<any>;
    filters?: DataFilters;
    searchUrl?: string;
    trashedCount?: any;
    pagination?: PaginationRootLink & PaginationMeta;
    onCreate?: () => void;
    onImport?: () => void;
    onExport?: () => void;
    disableCreate?: boolean;
    disableImport?: boolean;
    disableExport?: boolean;
    showArchivedFilter?: boolean;
    draggableUpdateUrl?: string;
    dragItems?: boolean;
    useApi?: boolean;
    apiFetchAction?: (url: string) => void | Promise<void>;
    pageSize?: number;
}

const props = withDefaults(defineProps<Props>(), {
    disableCreate: false,
    disableImport: false,
    disableExport: false,
    showArchivedFilter: true,
    dragItems: false,
    useApi: false,
    pageSize: PAGINATION_ITEMS_PER_PAGE,
});

const filter = ref(props?.filters?.search ?? '');
const trashed = ref(props?.filters?.trashed ?? '0');
const pageSize = ref(props?.pagination?.per_page ?? props.pageSize);
const currentPage = ref(props?.pagination?.current_page ?? '1');
const { initialize, toggleColumnVisibility, tableSearch, setPageSize, goToPage, loadTrashed } = useDataTables();
const table = initialize(props);

const searchWatcher = tableSearch(pageSize, currentPage, trashed, props.searchUrl, props.useApi, props.apiFetchAction);
const pageSizeWatcher = setPageSize(filter, table, currentPage, trashed, props.searchUrl, props.useApi, props.apiFetchAction);
const goToPageWatcher = goToPage(filter, pageSize, trashed, props.searchUrl, props.useApi, props.apiFetchAction);
const trashedWatcher = loadTrashed(filter, pageSize, currentPage, props.searchUrl, props.useApi, props.apiFetchAction);

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
    <div class="bg-sidebar inline-block min-w-full overflow-auto rounded-xl px-6 pt-4 pb-6 align-middle">
        <div class="text-sidebar-foreground mt-3 mb-6 flex w-full justify-between text-sm">
            <div class="flex w-full items-center space-x-3">
                <Search v-model="filter" v-if="searchUrl || apiFetchAction" />
                <Archived v-if="showArchivedFilter" :handle-archived="handleArchived" :trashed="+trashed" :trashed-count="trashedCount" />
                <slot name="head-left" />
            </div>
            <div class="flex w-1/2 items-center justify-end space-x-3">
                <ColumnFilter :variant="ColorVariant.primary_outline" :table="table" :toggleColumnVisibility="toggleColumnVisibility" />
                <ExportButton :variant="ColorVariant.primary_outline" class="rounded-full" v-if="onExport" @click="() => (onExport ? onExport() : null)" :disable="disableExport" />
                <ImportButton :variant="ColorVariant.primary_outline" class="rounded-full" v-if="onImport" @click="() => (onImport ? onImport() : null)" :disable="disableImport" />
                <CreateButton :variant="ColorVariant.primary_outline" class="rounded-full" v-if="onCreate" @click="() => (onCreate ? onCreate() : null)" :disable="disableCreate" />
                <slot name="head-right" />
            </div>
        </div>
        <div v-if="dragItems" class="text-primary my-2 flex w-1/4 rounded-full bg-slate-200 px-3 py-1 text-xs font-bold">
            {{ $t('trans.draggable_description') }}
        </div>
        <table class="hava-table">
            <TableHead :table="table" />
            <TableBody :table="table" :drag-items="dragItems" :draggable-update-url="draggableUpdateUrl" />
        </table>
    </div>
    <div class="my-3 flex w-full items-center justify-between px-6" v-if="pagination">
        <PerPageSize v-model="pageSize" />
        <GotoPage v-model="currentPage" :meta="pagination ?? null" />
        <Paginator :meta="pagination" />
    </div>
</template>
