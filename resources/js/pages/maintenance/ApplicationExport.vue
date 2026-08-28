<script setup lang="ts">
import BaseCombobox from '@/components/core/form/combobox/BaseCombobox.vue';
import BaseDatePicker from '@/components/core/form/date/BaseDatePicker.vue';
import PageContainer from '@/components/core/page/PageContainer.vue';
import DataTable from '@/components/core/table/DataTable.vue';
import HeadingSmall from '@/components/core/util/HeadingSmall.vue';
import MaintenanceExportActionBar from '@/components/maintenance/exports/MaintenanceExportActionBar.vue';
import MaintenanceExportBreakdownCard from '@/components/maintenance/exports/MaintenanceExportBreakdownCard.vue';
import StudentExportFilters from '@/components/students/filters/StudentExportFilters.vue';
import { successAlert } from '@/lib/alerts';
import { mergeQueryParamsIntoRequestPath } from '@/lib/merge-query-into-url';
import type { AuthObject, DataListProps } from '@/types/data-pagination';
import type { IntakePeriod } from '@/types/institution';
import type {
    ApplicationExportFiltersState,
    ApplicationExportPreviewRow,
    ApplicationExportStats,
} from '@/types/maintenance-exports';
import type { StudentFiltersState } from '@/types/students';
import type { BreadcrumbItemInterface } from '@/types/ui';
import type { SelectOption } from '@/types/utils';
import { Head, router, useForm, usePage } from '@inertiajs/vue3';
import { useDebounceFn } from '@vueuse/core';
import { trans, trans_choice } from 'laravel-vue-i18n';
import { computed, h, ref } from 'vue';

const props = defineProps<{
    filters: ApplicationExportFiltersState;
    stats: ApplicationExportStats;
    applications: DataListProps<ApplicationExportPreviewRow>;
    intakePeriods: IntakePeriod[];
}>();

const page = usePage<{ auth: AuthObject }>();

const breadcrumbs: BreadcrumbItemInterface[] = [
    { transKey: 'trans.maintenance', href: route('maintenance.index') },
    { transKey: 'trans.maintenance_export_applications' },
];

const activeFilters = ref<ApplicationExportFiltersState>({ ...props.filters });

const intakePeriodOptions = computed<SelectOption[]>(() =>
    (props.intakePeriods ?? []).map((period) => ({
        value: Number(period.id),
        label: period.attributes?.name ?? String(period.id),
    })),
);

const intakePeriodSelection = ref<SelectOption | null>(
    intakePeriodOptions.value.find((option) => Number(option.value) === Number(props.filters.intake_period_id)) ?? null,
);

const appliedFrom = ref<string | null>(props.filters.applied_from ?? null);
const appliedTo = ref<string | null>(props.filters.applied_to ?? null);

const toDateString = (value: unknown): string | undefined => {
    if (!value) {
        return undefined;
    }

    const date = value instanceof Date ? value : new Date(String(value));

    return Number.isNaN(date.getTime()) ? undefined : date.toISOString().slice(0, 10);
};

const reload = useDebounceFn((filters: ApplicationExportFiltersState): void => {
    activeFilters.value = filters;

    router.get(route('maintenance.exports.application.preview'), filters as Record<string, unknown>, {
        preserveState: true,
        preserveScroll: true,
        replace: true,
        only: ['filters', 'stats', 'applications'],
    });
}, 400);

const onStudentFiltersChange = (studentFilters: StudentFiltersState): void => {
    reload({
        ...activeFilters.value,
        search: studentFilters.search ?? undefined,
        department: studentFilters.department ?? undefined,
        level: studentFilters.level ?? undefined,
        course: studentFilters.course ?? undefined,
        mode_of_study: studentFilters.mode_of_study ?? undefined,
        gender: studentFilters.gender ?? undefined,
        student_type: studentFilters.student_type ?? undefined,
        sponsored: studentFilters.sponsored ?? undefined,
        disability: studentFilters.disability ?? undefined,
    });
};

const onIntakeFilterChange = (): void => {
    reload({
        ...activeFilters.value,
        intake_period_id: intakePeriodSelection.value?.value ? Number(intakePeriodSelection.value.value) : undefined,
        applied_from: toDateString(appliedFrom.value),
        applied_to: toDateString(appliedTo.value),
    });
};

const studentFilters = computed<StudentFiltersState>(() => ({
    search: props.filters.search ?? undefined,
    department: props.filters.department ?? undefined,
    level: props.filters.level ?? undefined,
    course: props.filters.course ?? undefined,
    mode_of_study: props.filters.mode_of_study ?? undefined,
    gender: props.filters.gender ?? undefined,
    student_type: props.filters.student_type ?? undefined,
    sponsored: props.filters.sponsored ?? undefined,
    disability: props.filters.disability ?? undefined,
}));

const exportForm = useForm({
    recipient_emails: page.props.auth?.user?.attributes?.email ?? '',
});

const recipientEmailsError = computed(() => {
    if (exportForm.errors.recipient_emails) {
        return exportForm.errors.recipient_emails;
    }

    return Object.entries(exportForm.errors)
        .filter(([key]) => key.startsWith('recipient_emails.'))
        .map(([, message]) => message)
        .join(' ');
});

const submitExport = (): void => {
    exportForm
        .transform((data) => ({ ...activeFilters.value, ...data }))
        .post(route('maintenance.exports.application'), {
            preserveScroll: true,
            onSuccess: () => successAlert(trans('trans.maintenance_export_application_queued_message')),
        });
};

const previewUrl = computed(() =>
    mergeQueryParamsIntoRequestPath(
        route('maintenance.exports.application.preview'),
        activeFilters.value as Record<string, unknown>,
    ),
);

const textCell = (value: string | null | undefined) => h('span', { class: 'text-sm' }, value || '---');

const columns = [
    {
        header: trans_choice('trans.student_number', 1),
        accessorKey: 'attributes.studentNumber',
        cell: ({ row }: { row: { original: ApplicationExportPreviewRow } }) =>
            h('span', { class: 'font-mono text-xs' }, row.original.attributes.studentNumber || '---'),
    },
    {
        header: trans_choice('trans.name', 1),
        accessorKey: 'attributes.name',
        cell: ({ row }: { row: { original: ApplicationExportPreviewRow } }) => textCell(row.original.attributes.name),
    },
    {
        header: trans_choice('trans.department', 1),
        accessorKey: 'attributes.department',
        cell: ({ row }: { row: { original: ApplicationExportPreviewRow } }) => textCell(row.original.attributes.department),
    },
    {
        header: trans_choice('trans.level', 1),
        accessorKey: 'attributes.level',
        cell: ({ row }: { row: { original: ApplicationExportPreviewRow } }) => textCell(row.original.attributes.level),
    },
    {
        header: trans_choice('trans.course', 1),
        accessorKey: 'attributes.course',
        cell: ({ row }: { row: { original: ApplicationExportPreviewRow } }) => textCell(row.original.attributes.course),
    },
    {
        header: trans('trans.maintenance_export_filter_intake_period'),
        accessorKey: 'attributes.intakePeriod',
        cell: ({ row }: { row: { original: ApplicationExportPreviewRow } }) => textCell(row.original.attributes.intakePeriod),
    },
    {
        header: trans_choice('trans.status', 1),
        accessorKey: 'attributes.applicationStatus',
        cell: ({ row }: { row: { original: ApplicationExportPreviewRow } }) =>
            textCell(row.original.attributes.applicationStatus),
    },
    {
        header: trans('trans.maintenance_export_filter_applied_from'),
        accessorKey: 'attributes.appliedAt',
        cell: ({ row }: { row: { original: ApplicationExportPreviewRow } }) => textCell(row.original.attributes.appliedAt),
    },
];
</script>

<template>
    <Head :title="trans('trans.maintenance_export_applications')" />

    <PageContainer :breadcrumbs="breadcrumbs">
        <div class="space-y-4">
            <HeadingSmall
                :title="trans('trans.maintenance_export_applications')"
                :description="trans('trans.maintenance_export_applications_page_description')"
            />

            <div class="space-y-3 rounded-xl border border-border bg-card p-4">
                <StudentExportFilters :filters="studentFilters" @change="onStudentFiltersChange" />

                <div class="grid grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-3">
                    <BaseCombobox
                        v-model="intakePeriodSelection"
                        :options="intakePeriodOptions"
                        :placeholder="trans('trans.maintenance_export_filter_intake_period')"
                        class="rounded-full"
                        @update:model-value="onIntakeFilterChange"
                    />
                    <BaseDatePicker
                        v-model="appliedFrom"
                        input-id="applied_from"
                        :label="trans('trans.maintenance_export_filter_applied_from')"
                        :enable-time-picker="false"
                        @update:model-value="onIntakeFilterChange"
                    />
                    <BaseDatePicker
                        v-model="appliedTo"
                        input-id="applied_to"
                        :label="trans('trans.maintenance_export_filter_applied_to')"
                        :enable-time-picker="false"
                        @update:model-value="onIntakeFilterChange"
                    />
                </div>
            </div>

            <div class="grid grid-cols-1 gap-3 md:grid-cols-2">
                <MaintenanceExportBreakdownCard
                    :title="trans('trans.maintenance_export_by_status')"
                    :rows="stats.byWorkflowStep"
                />
                <MaintenanceExportBreakdownCard :title="trans('trans.maintenance_export_by_level')" :rows="stats.byLevel" />
            </div>

            <MaintenanceExportActionBar
                v-model:recipient-emails="exportForm.recipient_emails"
                :total="stats.total"
                :processing="exportForm.processing"
                :error="recipientEmailsError"
                @export="submitExport"
            />

            <div class="space-y-2">
                <HeadingSmall
                    :title="trans('trans.maintenance_export_preview_table_title')"
                    :description="trans('trans.maintenance_export_preview_table_description')"
                />

                <DataTable
                    :data="applications.data"
                    :columns="columns"
                    :pagination="{ ...applications.links, ...applications.meta }"
                    :search-url="previewUrl"
                    :disable-create="true"
                    :disable-import="true"
                    :disable-export="true"
                    :show-archived-filter="false"
                    :show-column-filters="false"
                    :hide-built-in-search="true"
                />
            </div>
        </div>
    </PageContainer>
</template>
