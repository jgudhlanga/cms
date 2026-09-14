<script setup lang="ts">
import PageContainer from '@/components/core/page/PageContainer.vue';
import { BaseButton } from '@/components/core/button';
import Empty from '@/components/core/util/Empty.vue';
import { ButtonSize } from '@/enums/buttons';
import { ColorVariant } from '@/enums/colors';
import { openModal } from '@/lib/alerts';
import { APP_MODULE_KEYS } from '@/lib/constants';
import EscalateMissingMarksModal from '@/pages/institution/assessments/partials/EscalateMissingMarksModal.vue';
import type { AuthObject } from '@/types/data-pagination';
import type {
    MissingMarksReportFilterOption,
    MissingMarksReportFilterOptions,
    MissingMarksReportFilters,
    MissingMarksReportRow,
} from '@/types/assessments';
import type { BreadcrumbItemInterface } from '@/types/ui';
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import { trans, trans_choice } from 'laravel-vue-i18n';
import { computed } from 'vue';

const props = defineProps<{
    rows: MissingMarksReportRow[];
    filters: MissingMarksReportFilters;
    filterOptions: MissingMarksReportFilterOptions;
    availableYears: number[];
    currentYear: number;
    isHistorical: boolean;
    canExport: boolean;
    canEscalate: boolean;
    canRemind: boolean;
    auth: AuthObject;
}>();

type FilterParam =
    | 'institution_department_id'
    | 'level_id'
    | 'course_id'
    | 'module_id'
    | 'lecturer_staff_id'
    | 'assessment_type_id';

interface FilterField {
    param: FilterParam;
    id: string;
    label: string;
    value: number | null;
    options: MissingMarksReportFilterOption[];
    /** The filter that has to be chosen before this one lists anything. */
    waitsFor: string | null;
}

/** Each filter's options depend on the ones before it, so changing one clears those after it. */
const narrowingOrder: FilterParam[] = ['institution_department_id', 'level_id', 'course_id', 'module_id', 'lecturer_staff_id'];

const breadcrumbs = computed<BreadcrumbItemInterface[]>(() => [
    { transKey: 'trans.dashboard', href: route('dashboard') },
    { title: trans('assessments.missing_marks_report_title') },
]);

const filterFields = computed<FilterField[]>(() => {
    const department = trans_choice('trans.department', 1);
    const level = trans_choice('trans.level', 1);
    const course = trans_choice('trans.course', 1);
    const module = trans_choice('trans.module', 1);

    return [
        {
            param: 'institution_department_id',
            id: 'missing-marks-department',
            label: department,
            value: props.filters.departmentId,
            options: props.filterOptions.departments,
            waitsFor: null,
        },
        {
            param: 'level_id',
            id: 'missing-marks-level',
            label: level,
            value: props.filters.levelId,
            options: props.filterOptions.levels,
            waitsFor: props.filters.departmentId ? null : department,
        },
        {
            param: 'course_id',
            id: 'missing-marks-course',
            label: course,
            value: props.filters.courseId,
            options: props.filterOptions.courses,
            waitsFor: props.filters.levelId ? null : level,
        },
        {
            param: 'module_id',
            id: 'missing-marks-module',
            label: module,
            value: props.filters.moduleId,
            options: props.filterOptions.modules,
            waitsFor: props.filters.courseId ? null : course,
        },
        {
            param: 'lecturer_staff_id',
            id: 'missing-marks-lecturer',
            label: trans('assessments.missing_marks_lecturer'),
            value: props.filters.lecturerStaffId,
            options: props.filterOptions.lecturers,
            waitsFor: props.filters.moduleId ? null : module,
        },
        {
            param: 'assessment_type_id',
            id: 'missing-marks-assessment-type',
            label: trans_choice('trans.assessment_type', 1),
            value: props.filters.assessmentTypeId,
            options: props.filterOptions.assessmentTypes,
            waitsFor: null,
        },
    ];
});

const toParam = (value: number | null): string | undefined => (value ? String(value) : undefined);

const queryParams = computed<Record<string, string | undefined>>(() => ({
    calendar_year: props.filters.calendarYear !== props.currentYear ? String(props.filters.calendarYear) : undefined,
    institution_department_id: toParam(props.filters.departmentId),
    level_id: toParam(props.filters.levelId),
    course_id: toParam(props.filters.courseId),
    module_id: toParam(props.filters.moduleId),
    lecturer_staff_id: toParam(props.filters.lecturerStaffId),
    assessment_type_id: toParam(props.filters.assessmentTypeId),
}));

const exportUrl = computed(() => route('missing-marks-report.export', queryParams.value));

const yearUrl = (year: number): string =>
    year === props.currentYear
        ? route('missing-marks-report.index')
        : route('missing-marks-report.index', { calendar_year: String(year) });

const applyFilter = (param: FilterParam, value: string): void => {
    const params: Record<string, string | undefined> = { ...queryParams.value, [param]: value || undefined };
    const position = narrowingOrder.indexOf(param);

    if (position !== -1) {
        narrowingOrder.slice(position + 1).forEach((below) => {
            params[below] = undefined;
        });
    }

    router.get(route('missing-marks-report.index'), params, { preserveState: true, preserveScroll: true, replace: true });
};

const showActions = computed(() => !props.isHistorical && (props.canEscalate || props.canRemind));

const remindForm = useForm({
    assessment_calendar_id: 0,
});

const remind = (calendarId: number): void => {
    remindForm.assessment_calendar_id = calendarId;
    remindForm.post(route('missing-marks-report.remind'), { preserveScroll: true });
};

const escalate = (row: MissingMarksReportRow): void => {
    openModal({
        name: APP_MODULE_KEYS.missing_marks_escalate,
        edit: {
            assessmentCalendarId: row.assessmentCalendarId,
            assessmentTypeName: row.assessmentTypeName,
            dueDate: row.dueDate,
        },
    });
};

const programmeLabel = (row: MissingMarksReportRow): string =>
    [row.courseName, row.levelName].filter((part) => part !== '').join(' · ');
</script>

<template>
    <Head :title="$t('assessments.missing_marks_report_title')" />
    <PageContainer :breadcrumbs="breadcrumbs" :back-url="route('dashboard')">
        <div class="space-y-4">
            <!-- Earlier years are offered only when they have assessment calendars. -->
            <nav
                v-if="availableYears.length > 1"
                :aria-label="$tChoice('academic_calendar.calendar_year', 1)"
                class="flex flex-wrap items-center gap-2 text-sm"
            >
                <span class="text-muted-foreground">{{ $tChoice('academic_calendar.calendar_year', 1) }}:</span>
                <template v-for="year in availableYears" :key="year">
                    <span v-if="year === filters.calendarYear" aria-current="page" class="rounded-md bg-muted px-2 py-1 font-semibold">{{ year }}</span>
                    <Link v-else :href="yearUrl(year)" class="rounded-md px-2 py-1 text-primary hover:underline">{{ year }}</Link>
                </template>
            </nav>
            <p v-else class="text-sm">
                <span class="text-muted-foreground">{{ $tChoice('academic_calendar.calendar_year', 1) }}:</span>
                <span class="font-semibold">{{ filters.calendarYear }}</span>
            </p>

            <p
                v-if="isHistorical"
                role="status"
                class="rounded-md border border-amber-300 bg-amber-50 px-3 py-2 text-sm text-amber-900 dark:border-amber-800 dark:bg-amber-950/40 dark:text-amber-200"
            >
                {{ $t('assessments.missing_marks_historical_notice', { year: String(filters.calendarYear) }) }}
            </p>

            <div class="flex flex-wrap items-end gap-3">
                <div class="grid min-w-0 flex-1 grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-3 2xl:grid-cols-6">
                    <div v-for="field in filterFields" :key="field.param" class="flex min-w-0 flex-col gap-1">
                        <label :for="field.id" class="text-xs font-medium text-muted-foreground">{{ field.label }}</label>
                        <select
                            :id="field.id"
                            class="h-9 w-full min-w-0 rounded-md border border-border bg-background px-2 text-sm text-foreground disabled:cursor-not-allowed disabled:bg-muted disabled:text-muted-foreground"
                            :value="field.value ?? ''"
                            :disabled="field.waitsFor !== null"
                            @change="applyFilter(field.param, ($event.target as HTMLSelectElement).value)"
                        >
                            <option value="">
                                {{
                                    field.waitsFor !== null
                                        ? $t('assessments.missing_marks_choose_first', { filter: field.waitsFor.toLowerCase() })
                                        : $t('assessments.missing_marks_all')
                                }}
                            </option>
                            <option v-for="option in field.options" :key="option.id" :value="option.id">
                                {{ option.label }}
                            </option>
                        </select>
                    </div>
                </div>
                <!-- Same height as the selects, so the button sits centred on the filter row. -->
                <div v-if="canExport" class="flex h-9 items-center">
                    <a :href="exportUrl" class="inline-flex">
                        <BaseButton type="button" :variant="ColorVariant.primary_outline" :size="ButtonSize.xs">
                            {{ $t('assessments.missing_marks_export') }}
                        </BaseButton>
                    </a>
                </div>
            </div>

            <div v-if="rows.length === 0" class="rounded-xl border border-border bg-card p-6">
                <Empty :message="$t('assessments.missing_marks_report_empty')" />
            </div>
            <div v-else class="overflow-x-auto rounded-xl border border-border bg-card">
                <table class="w-full min-w-205 border-collapse text-left text-sm">
                    <caption class="sr-only">
                        {{ $t('assessments.missing_marks_report_title') }} · {{ filters.calendarYear }}
                    </caption>
                    <thead>
                        <tr class="border-b border-border bg-muted/40">
                            <th scope="col" class="px-3 py-2 font-medium">{{ $tChoice('trans.class', 1) }}</th>
                            <th scope="col" class="px-3 py-2 font-medium">{{ $tChoice('trans.module', 1) }}</th>
                            <th scope="col" class="px-3 py-2 font-medium">{{ $t('assessments.missing_marks_lecturer') }}</th>
                            <th scope="col" class="px-3 py-2 font-medium">{{ $t('dashboard.academic_incomplete') }}</th>
                            <th scope="col" class="px-3 py-2 font-medium">{{ $t('assessments.missing_marks_due_date') }}</th>
                            <th scope="col" class="px-3 py-2 font-medium">{{ $t('assessments.missing_marks_last_tier') }}</th>
                            <th v-if="showActions" scope="col" class="px-3 py-2 font-medium">
                                <span class="sr-only">{{ $tChoice('trans.action', 2) }}</span>
                            </th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr
                            v-for="(row, index) in rows"
                            :key="`${row.assessmentCalendarId}-${row.className}-${row.moduleCode}-${index}`"
                            class="border-b border-border align-top last:border-0"
                        >
                            <td class="px-3 py-2">
                                <div class="font-medium">{{ row.className }}</div>
                                <div v-if="programmeLabel(row)" class="text-xs text-muted-foreground">{{ programmeLabel(row) }}</div>
                                <div class="text-xs text-muted-foreground">{{ row.departmentName }}</div>
                            </td>
                            <td class="px-3 py-2">
                                <div>{{ row.moduleCode ? `${row.moduleCode} · ${row.moduleName}` : row.moduleName }}</div>
                                <div class="text-xs text-muted-foreground">{{ row.assessmentTypeName }}</div>
                            </td>
                            <td class="px-3 py-2">{{ row.lecturerNames }}</td>
                            <td class="px-3 py-2">{{ row.incompleteCount }}</td>
                            <td class="px-3 py-2">{{ row.dueDate ?? '—' }}</td>
                            <td class="px-3 py-2">{{ row.lastTierLabel ?? $t('assessments.missing_marks_none') }}</td>
                            <td v-if="showActions" class="px-3 py-2">
                                <div class="flex flex-wrap gap-1.5">
                                    <BaseButton
                                        v-if="canRemind"
                                        type="button"
                                        :variant="ColorVariant.primary_outline"
                                        :size="ButtonSize.xs"
                                        :processing="remindForm.processing"
                                        @click="remind(row.assessmentCalendarId)"
                                    >
                                        {{ $t('assessments.missing_marks_remind') }}
                                    </BaseButton>
                                    <BaseButton
                                        v-if="canEscalate && !row.escalated"
                                        type="button"
                                        :variant="ColorVariant.danger_outline"
                                        :size="ButtonSize.xs"
                                        @click="escalate(row)"
                                    >
                                        {{ $t('assessments.missing_marks_escalate') }}
                                    </BaseButton>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <EscalateMissingMarksModal v-if="canEscalate && !isHistorical" />
    </PageContainer>
</template>
