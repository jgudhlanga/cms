<script setup lang="ts">
import GenericButton from '@/components/core/button/GenericButton.vue';
import FinancialSummaryCard from '@/components/finance/FinancialSummaryCard.vue';
import BillingExportFilters from '@/components/finance/filters/BillingExportFilters.vue';
import PageContainer from '@/components/core/page/PageContainer.vue';
import DataTable from '@/components/core/table/DataTable.vue';
import HeadingSmall from '@/components/core/util/HeadingSmall.vue';
import { useBillingExportRecords } from '@/composables/finance/useBillingExportRecords';
import { ColorVariant } from '@/enums/colors';
import { IconName } from '@/enums/icons';
import { errorAlert, successAlert } from '@/lib/alerts';
import { mergeQueryParamsIntoRequestPath } from '@/lib/merge-query-into-url';
import type { AuthObject, DataListProps } from '@/types/data-pagination';
import type {
    BillingExportFilterOptions,
    BillingExportFiltersState,
    BillingExportPeriodOption,
    BillingExportStats,
    StudentBillingRecord,
} from '@/types/finance';
import type { Link } from '@/types/ui';
import { CheckCircle, Download, Receipt } from '@lucide/vue';
import { Head, router } from '@inertiajs/vue3';
import { useDebounceFn } from '@vueuse/core';
import axios from 'axios';
import { trans } from 'laravel-vue-i18n';
import { computed, ref } from 'vue';

const props = defineProps<{
    auth: AuthObject;
    errors: object;
    periodOptions: BillingExportPeriodOption[];
    filterOptions: BillingExportFilterOptions;
    filters: BillingExportFiltersState;
    exportCount: number | null;
    billingStats: BillingExportStats;
    billingRecords: DataListProps<StudentBillingRecord>;
}>();

const breadcrumbs: Array<Link> = [
    { transChoiceKey: 'finance.finance', transChoiceKeyIndex: 1, href: route('finance.index') },
    { transChoiceKey: 'finance.billing_export' },
];

const visibleBillingRecords = computed(() => props.billingRecords.data ?? []);

const {
    createBillingRecordColumns,
    markBilled,
    markFailed,
    unbill,
    selectedCount,
    selectedExportedIds,
    selectedBilledIds,
    clearSelection,
} = useBillingExportRecords(visibleBillingRecords);

const billingRecordColumns = computed(() => {
    void selectedCount.value;

    return createBillingRecordColumns();
});

const activeFilters = ref<BillingExportFiltersState>({ ...props.filters });

const isExporting = ref(false);

const canExport = computed(
    () => (activeFilters.value.academic_calendar_ids ?? []).length > 0,
);

const readyToBillValue = computed(() => {
    if (props.billingStats.readyToBill === null) {
        return '—';
    }

    return String(props.billingStats.readyToBill);
});

const filterQuery = computed(() => ({
    academic_calendar_ids: activeFilters.value.academic_calendar_ids,
    programme_semester_ids: activeFilters.value.programme_semester_ids,
    sources: activeFilters.value.sources,
    sync_statuses: activeFilters.value.sync_statuses,
    student_number_starts_with: activeFilters.value.student_number_starts_with,
    confirmed_from: activeFilters.value.confirmed_from,
    confirmed_to: activeFilters.value.confirmed_to,
    institution_department_id: activeFilters.value.institution_department_id,
    department_level_id: activeFilters.value.department_level_id,
    department_course_id: activeFilters.value.department_course_id,
    mode_of_study_id: activeFilters.value.mode_of_study_id,
    pastel_linked: activeFilters.value.pastel_linked,
}));

const billingRecordsSearchUrl = computed(() => {
    return mergeQueryParamsIntoRequestPath(route('finance.billing-export.index'), filterQuery.value);
});

const applyFilters = useDebounceFn((filters: BillingExportFiltersState): void => {
    activeFilters.value = filters;
    clearSelection();

    router.get(
        route('finance.billing-export.index'),
        {
            ...filters,
            search: props.filters.search ?? undefined,
        },
        {
            preserveState: true,
            preserveScroll: true,
            replace: true,
            only: ['filters', 'exportCount', 'billingStats', 'billingRecords', 'filterOptions'],
        },
    );
}, 400);

const handleFilterChange = (filters: BillingExportFiltersState): void => {
    applyFilters(filters);
};

const resolveDownloadFileName = (contentDisposition: string | undefined): string => {
    if (!contentDisposition) {
        return `billing-export-${new Date().toISOString().slice(0, 19).replace(/[:T]/g, '-')}.csv`;
    }

    const utfMatch = contentDisposition.match(/filename\*=UTF-8''([^;]+)/i);
    if (utfMatch?.[1]) {
        return decodeURIComponent(utfMatch[1]);
    }

    const plainMatch = contentDisposition.match(/filename="?([^";]+)"?/i);

    return plainMatch?.[1] ?? `billing-export-${Date.now()}.csv`;
};

const reloadExportProps = (): void => {
    router.reload({
        only: ['exportCount', 'billingStats', 'billingRecords', 'filters', 'filterOptions'],
        preserveScroll: true,
    });
};

const handleExport = async (): Promise<void> => {
    if (!canExport.value || isExporting.value) {
        return;
    }

    isExporting.value = true;

    try {
        const response = await axios.post(
            route('finance.billing-export.download'),
            {
                academic_calendar_ids: activeFilters.value.academic_calendar_ids ?? [],
                programme_semester_ids: activeFilters.value.programme_semester_ids ?? [],
                sources: activeFilters.value.sources ?? [],
                sync_statuses: activeFilters.value.sync_statuses ?? [],
                student_number_starts_with: activeFilters.value.student_number_starts_with ?? '',
                confirmed_from: activeFilters.value.confirmed_from ?? '',
                confirmed_to: activeFilters.value.confirmed_to ?? '',
                institution_department_id: activeFilters.value.institution_department_id ?? null,
                department_level_id: activeFilters.value.department_level_id ?? null,
                department_course_id: activeFilters.value.department_course_id ?? null,
                mode_of_study_id: activeFilters.value.mode_of_study_id ?? null,
                pastel_linked: activeFilters.value.pastel_linked ?? null,
            },
            {
                responseType: 'blob',
                withCredentials: true,
                xsrfCookieName: 'XSRF-TOKEN',
                xsrfHeaderName: 'X-XSRF-TOKEN',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    Accept: 'text/csv',
                },
            },
        );

        const blob = new Blob([response.data], { type: 'text/csv' });
        const objectUrl = URL.createObjectURL(blob);
        const anchor = document.createElement('a');
        anchor.href = objectUrl;
        anchor.download = resolveDownloadFileName(response.headers['content-disposition']);
        document.body.appendChild(anchor);
        anchor.click();
        anchor.remove();
        URL.revokeObjectURL(objectUrl);

        successAlert(trans('finance.billing_export_success'));
        reloadExportProps();
    } catch {
        errorAlert(trans('finance.billing_export_download_failed'));
    } finally {
        isExporting.value = false;
    }
};
</script>

<template>
    <Head :title="$t('finance.billing_export')" />
    <PageContainer :breadcrumbs="breadcrumbs">
        <HeadingSmall :title="$t('finance.billing_export')" :description="$t('finance.billing_export_description')" />

        <div class="mt-4 space-y-4">
            <div class="grid grid-cols-1 gap-3 md:grid-cols-3">
                <FinancialSummaryCard
                    title-key="finance.billing_export_billed_students"
                    hint-key="finance.billing_export_billed_students_hint"
                    :value="String(billingStats.total)"
                    :icon="Receipt"
                    icon-class="bg-indigo-500/15 text-indigo-600 dark:text-indigo-400"
                />
                <FinancialSummaryCard
                    title-key="finance.billing_export_ready_to_bill"
                    hint-key="finance.billing_export_ready_to_bill_hint"
                    :value="readyToBillValue"
                    :icon="Download"
                    icon-class="bg-emerald-500/15 text-emerald-600 dark:text-emerald-400"
                />
                <FinancialSummaryCard
                    title-key="finance.billing_export_billed_today"
                    hint-key="finance.billing_export_billed_today_hint"
                    :value="String(billingStats.billedToday)"
                    :icon="CheckCircle"
                    icon-class="bg-amber-500/15 text-amber-600 dark:text-amber-400"
                />
            </div>

            <BillingExportFilters
                :period-options="periodOptions"
                :filter-options="filterOptions"
                :filters="filters"
                @change="handleFilterChange"
            />

            <div class="flex flex-wrap items-center justify-between gap-3 rounded-lg border border-border p-4">
                <div class="space-y-1">
                    <p class="text-sm font-medium text-foreground">
                        {{ $t('finance.billing_export_ready') }}
                    </p>
                    <p v-if="exportCount !== null" class="text-xs text-muted-foreground">
                        {{ $tChoice('finance.billing_export_count', exportCount, { count: exportCount }) }}
                    </p>
                    <p v-else class="text-xs text-muted-foreground">
                        {{ $t('finance.billing_export_select_period') }}
                    </p>
                </div>

                <GenericButton
                    :icon="IconName.export"
                    :variant="ColorVariant.primary"
                    :title="$t('finance.billing_export_download')"
                    :disabled="!canExport || isExporting"
                    @click="handleExport"
                />
            </div>

            <div class="space-y-3">
                <div class="flex flex-wrap items-start justify-between gap-3">
                    <HeadingSmall
                        :title="$t('finance.billing_export_table_title')"
                        :description="$t('finance.billing_export_table_description')"
                    />

                    <div class="flex flex-wrap items-center gap-2">
                        <p v-if="selectedCount > 0" class="text-xs text-muted-foreground">
                            {{ $tChoice('finance.billing_export_selected_count', selectedCount, { count: selectedCount }) }}
                        </p>
                        <GenericButton
                            :variant="ColorVariant.primary_outline"
                            :title="$t('finance.billing_export_bulk_mark_billed')"
                            :disabled="selectedExportedIds.length === 0"
                            @click="markBilled(selectedExportedIds)"
                        />
                        <GenericButton
                            :variant="ColorVariant.danger_outline"
                            :title="$t('finance.billing_export_bulk_mark_failed')"
                            :disabled="selectedExportedIds.length === 0"
                            @click="markFailed(selectedExportedIds)"
                        />
                        <GenericButton
                            :variant="ColorVariant.danger_outline"
                            :title="$t('finance.billing_export_bulk_unbill')"
                            :disabled="selectedBilledIds.length === 0"
                            @click="unbill(selectedBilledIds, true)"
                        />
                    </div>
                </div>

                <DataTable
                    :data="billingRecords.data"
                    :filters="{ search: filters.search ?? null, trashed: 0 }"
                    :search-url="billingRecordsSearchUrl"
                    :pagination="{ ...billingRecords.links, ...billingRecords.meta }"
                    :columns="billingRecordColumns"
                    :disable-create="true"
                    :show-archived-filter="false"
                />
            </div>
        </div>
    </PageContainer>
</template>
