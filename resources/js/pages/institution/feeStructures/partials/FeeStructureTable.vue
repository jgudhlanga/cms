<script setup lang="ts">
import DataTable from '@/components/core/table/DataTable.vue';
import { useFeeStructures } from '@/composables/institution/useFeeStructures';
import { PAGINATION_ITEMS_PER_PAGE } from '@/lib/constants';
import { paginateLocally, parsePaginationFromUrl } from '@/lib/local-pagination';
import { hasAbility } from '@/lib/permissions';
import type { DataListProps } from '@/types/data-pagination';
import { FeeStructure } from '@/types/institution';
import { FeeType } from '@/types/settings';
import { computed, ref, watch } from 'vue';

interface Props {
    feeStructures?: FeeStructure[];
    feeType?: FeeType;
}

const props = defineProps<Props>();
const { createFeeStructureColumns, onOpenModal } = useFeeStructures();

const searchUrl = route('fee-structures.index');
const allRows = computed(() => props.feeStructures ?? []);

const emptyPageList = (): DataListProps<FeeStructure> =>
    paginateLocally([], searchUrl, 1, PAGINATION_ITEMS_PER_PAGE);

const pageList = ref<DataListProps<FeeStructure>>(emptyPageList());

const applyPagination = (page: number, pageSize: number) => {
    pageList.value = paginateLocally(allRows.value, searchUrl, page, pageSize);
};

const loadFromUrl = (url: string) => {
    const currentPageSize = pageList.value.meta.per_page ?? PAGINATION_ITEMS_PER_PAGE;
    const { page, pageSize } = parsePaginationFromUrl(url, {
        page: pageList.value.meta.current_page ?? 1,
        pageSize: currentPageSize,
    });
    applyPagination(page, pageSize);
};

watch(
    allRows,
    () => {
        const page = pageList.value.meta.current_page ?? 1;
        const pageSize = pageList.value.meta.per_page ?? PAGINATION_ITEMS_PER_PAGE;
        applyPagination(page, pageSize);
    },
    { immediate: true, deep: true },
);
</script>

<template>
    <DataTable
        :data="pageList.data"
        :pagination="{ ...pageList.links, ...pageList.meta }"
        :use-api="true"
        :search-url="searchUrl"
        :api-fetch-action="loadFromUrl"
        :columns="createFeeStructureColumns(feeType)"
        :show-archived-filter="false"
        :show-column-filters="false"
        :hide-built-in-search="true"
        :on-create="() => onOpenModal(undefined, props.feeType)"
        :disable-create="!hasAbility('create:fee-structures')"
    />
</template>
