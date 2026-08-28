<script setup lang="ts">
import BaseCombobox from '@/components/core/form/combobox/BaseCombobox.vue';
import PageContainer from '@/components/core/page/PageContainer.vue';
import DataTable from '@/components/core/table/DataTable.vue';
import HeadingSmall from '@/components/core/util/HeadingSmall.vue';
import MaintenanceExportActionBar from '@/components/maintenance/exports/MaintenanceExportActionBar.vue';
import MaintenanceExportBreakdownCard from '@/components/maintenance/exports/MaintenanceExportBreakdownCard.vue';
import StudentExportFilters from '@/components/students/filters/StudentExportFilters.vue';
import { successAlert } from '@/lib/alerts';
import { mergeQueryParamsIntoRequestPath } from '@/lib/merge-query-into-url';
import type { AuthObject, DataListProps } from '@/types/data-pagination';
import type {
    MaintenanceCalendarTypeOption,
    MaintenanceSemesterOption,
    StudentEnrolmentExportFiltersState,
    StudentEnrolmentExportPreviewRow,
    StudentEnrolmentExportStats,
} from '@/types/maintenance-exports';
import type { StudentFiltersState } from '@/types/students';
import type { BreadcrumbItemInterface } from '@/types/ui';
import type { SelectOption } from '@/types/utils';
import { Head, router, useForm, usePage } from '@inertiajs/vue3';
import { useDebounceFn } from '@vueuse/core';
import { trans, trans_choice } from 'laravel-vue-i18n';
import { computed, h, ref } from 'vue';

const props = defineProps<{
    filters: StudentEnrolmentExportFiltersState;
    stats: StudentEnrolmentExportStats;
    enrolments: DataListProps<StudentEnrolmentExportPreviewRow>;
    calendarYears: string[];
    semesters: MaintenanceSemesterOption[];
    calendarTypes: MaintenanceCalendarTypeOption[];
}>();

const page = usePage<{ auth: AuthObject }>();

const breadcrumbs: BreadcrumbItemInterface[] = [
    { transKey: 'trans.maintenance', href: route('maintenance.index') },
    { transKey: 'trans.maintenance_export_student_enrolments' },
];

const activeFilters = ref<StudentEnrolmentExportFiltersState>({ ...props.filters });

const calendarYearOptions = computed<SelectOption[]>(() =>
    props.calendarYears.map((year) => ({ value: year, label: year })),
);

const semesterOptions = computed<SelectOption[]>(() =>
    props.semesters.map((semester) => ({ value: semester.id, label: semester.name })),
);

const calendarTypeOptions = computed<SelectOption[]>(() =>
    props.calendarTypes.map((type) => ({ value: type.value, label: type.label })),
);

const findOption = (options: SelectOption[], value: unknown): SelectOption | null =>
    options.find((option) => String(option.value) === String(value ?? '')) ?? null;

const calendarYearSelection = ref<SelectOption | null>(
    findOption(calendarYearOptions.value, props.filters.calendar_year),
);
const semesterSelection = ref<SelectOption | null>(findOption(semesterOptions.value, props.filters.semester_id));
const calendarTypeSelection = ref<SelectOption | null>(
    findOption(calendarTypeOptions.value, props.filters.calendar_type),
);

const reload = useDebounceFn((filters: StudentEnrolmentExportFiltersState): void => {
    activeFilters.value = filters;

    router.get(route('maintenance.exports.student-enrollment.preview'), filters as Record<string, unknown>, {
        preserveState: true,
        preserveScroll: true,
        replace: true,
        only: ['filters', 'stats', 'enrolments'],
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

const onCalendarFilterChange = (): void => {
    reload({
        ...activeFilters.value,
        calendar_year: (calendarYearSelection.value?.value as string | undefined) ?? undefined,
        semester_id: semesterSelection.value?.value ? Number(semesterSelection.value.value) : undefined,
        calendar_type: (calendarTypeSelection.value?.value as string | undefined) ?? undefined,
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
        .post(route('maintenance.exports.student-enrollment'), {
            preserveScroll: true,
            onSuccess: () => successAlert(trans('trans.maintenance_export_queued_message')),
        });
};

const previewUrl = computed(() =>
    mergeQueryParamsIntoRequestPath(
        route('maintenance.exports.student-enrollment.preview'),
        activeFilters.value as Record<string, unknown>,
    ),
);

const textCell = (value: string | null | undefined) => h('span', { class: 'text-sm' }, value || '---');

const columns = [
    {
        header: trans_choice('trans.student_number', 1),
        accessorKey: 'attributes.studentNumber',
        cell: ({ row }: { row: { original: StudentEnrolmentExportPreviewRow } }) =>
            h('span', { class: 'font-mono text-xs' }, row.original.attributes.studentNumber || '---'),
    },
    {
        header: trans_choice('trans.name', 1),
        accessorKey: 'attributes.name',
        cell: ({ row }: { row: { original: StudentEnrolmentExportPreviewRow } }) => textCell(row.original.attributes.name),
    },
    {
        header: trans_choice('trans.department', 1),
        accessorKey: 'attributes.department',
        cell: ({ row }: { row: { original: StudentEnrolmentExportPreviewRow } }) =>
            textCell(row.original.attributes.department),
    },
    {
        header: trans_choice('trans.level', 1),
        accessorKey: 'attributes.level',
        cell: ({ row }: { row: { original: StudentEnrolmentExportPreviewRow } }) => textCell(row.original.attributes.level),
    },
    {
        header: trans_choice('trans.course', 1),
        accessorKey: 'attributes.course',
        cell: ({ row }: { row: { original: StudentEnrolmentExportPreviewRow } }) => textCell(row.original.attributes.course),
    },
    {
        header: trans('trans.maintenance_export_filter_calendar_year'),
        accessorKey: 'attributes.calendarYear',
        cell: ({ row }: { row: { original: StudentEnrolmentExportPreviewRow } }) =>
            textCell(row.original.attributes.calendarYear),
    },
    {
        header: trans('trans.maintenance_export_filter_semester'),
        accessorKey: 'attributes.semester',
        cell: ({ row }: { row: { original: StudentEnrolmentExportPreviewRow } }) => textCell(row.original.attributes.semester),
    },
];
</script>

<template>
    <Head :title="trans('trans.maintenance_export_student_enrolments')" />

    <PageContainer :breadcrumbs="breadcrumbs">
        <div class="space-y-4">
            <HeadingSmall
                :title="trans('trans.maintenance_export_student_enrolments')"
                :description="trans('trans.maintenance_export_student_enrolments_page_description')"
            />

            <div class="space-y-3 rounded-xl border border-border bg-card p-4">
                <StudentExportFilters :filters="studentFilters" @change="onStudentFiltersChange" />

                <div class="grid grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-3">
                    <BaseCombobox
                        v-model="calendarYearSelection"
                        :options="calendarYearOptions"
                        :placeholder="trans('trans.maintenance_export_filter_calendar_year')"
                        class="rounded-full"
                        @update:model-value="onCalendarFilterChange"
                    />
                    <BaseCombobox
                        v-model="semesterSelection"
                        :options="semesterOptions"
                        :placeholder="trans('trans.maintenance_export_filter_semester')"
                        class="rounded-full"
                        @update:model-value="onCalendarFilterChange"
                    />
                    <BaseCombobox
                        v-model="calendarTypeSelection"
                        :options="calendarTypeOptions"
                        :placeholder="trans('trans.maintenance_export_filter_calendar_type')"
                        class="rounded-full"
                        @update:model-value="onCalendarFilterChange"
                    />
                </div>
            </div>

            <div class="grid grid-cols-1 gap-3 md:grid-cols-3">
                <MaintenanceExportBreakdownCard
                    :title="trans('trans.maintenance_export_by_level')"
                    :rows="stats.byLevel"
                />
                <MaintenanceExportBreakdownCard
                    :title="trans('trans.maintenance_export_by_gender')"
                    :rows="stats.byGender"
                />
                <MaintenanceExportBreakdownCard
                    :title="trans('trans.maintenance_export_by_mode_of_study')"
                    :rows="stats.byModeOfStudy"
                />
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
                    :data="enrolments.data"
                    :columns="columns"
                    :pagination="{ ...enrolments.links, ...enrolments.meta }"
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
