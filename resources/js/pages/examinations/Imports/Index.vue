<script setup lang="ts">
import { BaseButton } from '@/components/core/button';
import PageContainer from '@/components/core/page/PageContainer.vue';
import DataTable from '@/components/core/table/DataTable.vue';
import { ButtonSize } from '@/enums/buttons';
import { ColorVariant } from '@/enums/colors';
import type { DataListProps } from '@/types/data-pagination';
import type { Link } from '@/types/ui';
import { Head, Link as InertiaLink, router } from '@inertiajs/vue3';
import { trans } from 'laravel-vue-i18n';
import { computed, h } from 'vue';

type ImportRow = {
    id: number;
    sourceLabel: string;
    statusLabel: string;
    originalFilename: string;
    rowsTotal: number;
    rowsProcessed: number;
    rowsUpserted: number;
    rowsFailed: number;
    progressPercent: number;
    createdAt: string | null;
};

const props = defineProps<{
    imports: DataListProps<ImportRow>;
}>();

const breadcrumbs = computed<Link[]>(() => [
    { transChoiceKey: 'examinations.title', href: route('examinations.index') },
    { transKey: 'examinations.import_history' },
]);

const columns = computed(() => [
    {
        header: trans('examinations.import_file'),
        accessorKey: 'originalFilename',
        cell: ({ row }: { row: { original: ImportRow } }) =>
            h(
                InertiaLink,
                {
                    href: route('examinations.imports.show', row.original.id),
                    class: 'text-primary hover:underline',
                },
                () => row.original.originalFilename,
            ),
    },
    { header: trans('examinations.import_source'), accessorKey: 'sourceLabel' },
    { header: trans('examinations.import_status'), accessorKey: 'statusLabel' },
    { header: trans('examinations.rows_total'), accessorKey: 'rowsTotal' },
    { header: trans('examinations.rows_upserted'), accessorKey: 'rowsUpserted' },
    { header: trans('examinations.rows_failed'), accessorKey: 'rowsFailed' },
]);
</script>

<template>
    <Head :title="$t('examinations.import_history')" />
    <PageContainer :breadcrumbs="breadcrumbs" :back-url="route('examinations.index')">
        <DataTable
            :data="imports.data"
            :filters="{}"
            :search-url="route('examinations.imports.index')"
            :pagination="{ ...imports.links, ...imports.meta }"
            :columns="columns"
            :disable-create="true"
            :hide-built-in-search="true"
            :show-archived-filter="false"
        >
            <template #head-right>
                <BaseButton
                    :variant="ColorVariant.primary"
                    :size="ButtonSize.sm"
                    :title="$t('examinations.import_title')"
                    @click="router.visit(route('examinations.import'))"
                />
            </template>
        </DataTable>
    </PageContainer>
</template>
