<script setup lang="ts">
import GenericButton from '@/components/core/button/GenericButton.vue';
import FinancialSummaryCard from '@/components/finance/FinancialSummaryCard.vue';
import PastelExportFilters from '@/components/finance/filters/PastelExportFilters.vue';
import PageContainer from '@/components/core/page/PageContainer.vue';
import DataTable from '@/components/core/table/DataTable.vue';
import HeadingSmall from '@/components/core/util/HeadingSmall.vue';
import { usePastelLinkedStudents } from '@/composables/finance/usePastelLinkedStudents';
import { ColorVariant } from '@/enums/colors';
import { IconName } from '@/enums/icons';
import { errorAlert, successAlert } from '@/lib/alerts';
import { mergeQueryParamsIntoRequestPath } from '@/lib/merge-query-into-url';
import type { AuthObject, DataListProps } from '@/types/data-pagination';
import type { PastelExportFiltersState, PastelLinkedStats, PastelLinkedStudent } from '@/types/finance';
import { PASTEL_EXPORT_DEFAULT_STUDENT_NUMBER_PREFIX } from '@/types/finance';
import type { IntakePeriod } from '@/types/institution';
import type { WorkflowStep } from '@/types/settings';
import type { Link } from '@/types/ui';
import { CheckCircle, Download, Link2 } from '@lucide/vue';
import { Head, router } from '@inertiajs/vue3';
import { useDebounceFn } from '@vueuse/core';
import axios from 'axios';
import { trans } from 'laravel-vue-i18n';
import { computed, ref } from 'vue';

const props = defineProps<{
    auth: AuthObject;
    errors: object;
    intakePeriods: IntakePeriod[];
    workflowSteps: WorkflowStep[];
    filters: PastelExportFiltersState;
    exportCount: number | null;
    linkedStats: PastelLinkedStats;
    linkedStudents: DataListProps<PastelLinkedStudent>;
}>();

const breadcrumbs: Array<Link> = [
    { transChoiceKey: 'finance.finance', transChoiceKeyIndex: 1, href: route('finance.index') },
    { transChoiceKey: 'finance.pastel_export' },
];

const visibleLinkedStudents = computed(() => props.linkedStudents.data ?? []);

const { createLinkedStudentColumns, unlinkSelectedStudents, selectedCount, clearSelection } =
    usePastelLinkedStudents(visibleLinkedStudents);

const linkedStudentColumns = computed(() => {
    void selectedCount.value;

    return createLinkedStudentColumns();
});

const activeFilters = ref<PastelExportFiltersState>({
    intake_period_id: props.filters.intake_period_id,
    workflow_step_ids: props.filters.workflow_step_ids ?? [],
    student_number_starts_with:
        props.filters.student_number_starts_with ?? PASTEL_EXPORT_DEFAULT_STUDENT_NUMBER_PREFIX,
});

const isExporting = ref(false);

const canExport = computed(
    () => activeFilters.value.intake_period_id !== undefined && activeFilters.value.intake_period_id !== null,
);

const readyToExportValue = computed(() => {
    if (props.linkedStats.readyToExport === null) {
        return '—';
    }

    return String(props.linkedStats.readyToExport);
});

const linkedStudentsSearchUrl = computed(() => {
    return mergeQueryParamsIntoRequestPath(route('finance.pastel-export.index'), {
        intake_period_id: activeFilters.value.intake_period_id,
        workflow_step_ids: activeFilters.value.workflow_step_ids,
        student_number_starts_with: activeFilters.value.student_number_starts_with,
    });
});

const filtersAreEqual = (left: PastelExportFiltersState, right: PastelExportFiltersState): boolean => {
    if (left.intake_period_id !== right.intake_period_id) {
        return false;
    }

    if ((left.student_number_starts_with ?? '') !== (right.student_number_starts_with ?? '')) {
        return false;
    }

    const leftStepIds = [...(left.workflow_step_ids ?? [])].sort((a, b) => a - b);
    const rightStepIds = [...(right.workflow_step_ids ?? [])].sort((a, b) => a - b);

    return leftStepIds.length === rightStepIds.length && leftStepIds.every((id, index) => id === rightStepIds[index]);
};

const applyFilters = useDebounceFn((filters: PastelExportFiltersState): void => {
    if (filtersAreEqual(filters, props.filters)) {
        activeFilters.value = filters;

        return;
    }

    activeFilters.value = filters;
    clearSelection();

    router.get(
        route('finance.pastel-export.index'),
        {
            intake_period_id: filters.intake_period_id,
            workflow_step_ids: filters.workflow_step_ids,
            student_number_starts_with: filters.student_number_starts_with,
            search: props.filters.search ?? undefined,
        },
        {
            preserveState: true,
            preserveScroll: true,
            replace: true,
            only: ['filters', 'exportCount', 'linkedStats', 'linkedStudents'],
        },
    );
}, 400);

const handleFilterChange = (filters: PastelExportFiltersState): void => {
    applyFilters(filters);
};

const resolveDownloadFileName = (contentDisposition: string | undefined): string => {
    if (!contentDisposition) {
        return `pastel-export-${new Date().toISOString().slice(0, 19).replace(/[:T]/g, '-')}.csv`;
    }

    const utfMatch = contentDisposition.match(/filename\*=UTF-8''([^;]+)/i);
    if (utfMatch?.[1]) {
        return decodeURIComponent(utfMatch[1]);
    }

    const plainMatch = contentDisposition.match(/filename="?([^";]+)"?/i);

    return plainMatch?.[1] ?? `pastel-export-${Date.now()}.csv`;
};

const reloadExportProps = (): void => {
    router.reload({
        only: ['exportCount', 'linkedStats', 'linkedStudents', 'filters'],
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
            route('finance.pastel-export.download'),
            {
                intake_period_id: activeFilters.value.intake_period_id,
                workflow_step_ids: activeFilters.value.workflow_step_ids ?? [],
                student_number_starts_with: activeFilters.value.student_number_starts_with ?? '',
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

        successAlert(trans('finance.pastel_export_success'));
        reloadExportProps();
    } catch {
        errorAlert(trans('finance.pastel_export_download_failed'));
    } finally {
        isExporting.value = false;
    }
};
</script>

<template>
    <Head :title="$t('finance.pastel_export')" />
    <PageContainer :breadcrumbs="breadcrumbs">
        <HeadingSmall :title="$t('finance.pastel_export')" :description="$t('finance.pastel_export_description')" />

        <div class="mt-4 space-y-4">
            <div class="grid grid-cols-1 gap-3 md:grid-cols-3">
                <FinancialSummaryCard
                    title-key="finance.pastel_export_linked_students"
                    hint-key="finance.pastel_export_linked_students_hint"
                    :value="String(linkedStats.total)"
                    :icon="Link2"
                    icon-class="bg-indigo-500/15 text-indigo-600 dark:text-indigo-400"
                />
                <FinancialSummaryCard
                    title-key="finance.pastel_export_ready_to_export"
                    hint-key="finance.pastel_export_ready_to_export_hint"
                    :value="readyToExportValue"
                    :icon="Download"
                    icon-class="bg-emerald-500/15 text-emerald-600 dark:text-emerald-400"
                />
                <FinancialSummaryCard
                    title-key="finance.pastel_export_linked_today"
                    hint-key="finance.pastel_export_linked_today_hint"
                    :value="String(linkedStats.linkedToday)"
                    :icon="CheckCircle"
                    icon-class="bg-amber-500/15 text-amber-600 dark:text-amber-400"
                />
            </div>

            <PastelExportFilters
                :intake-periods="intakePeriods"
                :workflow-steps="workflowSteps"
                :filters="filters"
                @change="handleFilterChange"
            />

            <div class="flex flex-wrap items-center justify-between gap-3 rounded-lg border border-border p-4">
                <div class="space-y-1">
                    <p class="text-sm font-medium text-foreground">
                        {{ $t('finance.pastel_export_ready') }}
                    </p>
                    <p v-if="exportCount !== null" class="text-xs text-muted-foreground">
                        {{ $tChoice('finance.pastel_export_count', exportCount, { count: exportCount }) }}
                    </p>
                    <p v-else class="text-xs text-muted-foreground">
                        {{ $t('finance.pastel_export_select_intake') }}
                    </p>
                </div>

                <GenericButton
                    :icon="IconName.export"
                    :variant="ColorVariant.primary"
                    :title="$t('finance.pastel_export_download')"
                    :disabled="!canExport || isExporting"
                    @click="handleExport"
                />
            </div>

            <div class="space-y-3">
                <div class="flex flex-wrap items-start justify-between gap-3">
                    <HeadingSmall
                        :title="$t('finance.pastel_export_linked_table_title')"
                        :description="$t('finance.pastel_export_linked_table_description')"
                    />

                    <div class="flex flex-wrap items-center gap-2">
                        <p v-if="selectedCount > 0" class="text-xs text-muted-foreground">
                            {{ $tChoice('finance.pastel_export_selected_count', selectedCount, { count: selectedCount }) }}
                        </p>
                        <GenericButton
                            :variant="ColorVariant.danger_outline"
                            :title="$t('finance.pastel_export_bulk_unlink')"
                            :disabled="selectedCount === 0"
                            @click="unlinkSelectedStudents"
                        />
                    </div>
                </div>

                <DataTable
                    :data="linkedStudents.data"
                    :filters="{ search: filters.search ?? null, trashed: 0 }"
                    :search-url="linkedStudentsSearchUrl"
                    :pagination="{ ...linkedStudents.links, ...linkedStudents.meta }"
                    :columns="linkedStudentColumns"
                    :disable-create="true"
                    :show-archived-filter="false"
                />
            </div>
        </div>
    </PageContainer>
</template>
