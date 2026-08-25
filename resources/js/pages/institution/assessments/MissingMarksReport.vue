<script setup lang="ts">
import PageContainer from '@/components/core/page/PageContainer.vue';
import { BaseButton } from '@/components/core/button';
import Empty from '@/components/core/util/Empty.vue';
import { ButtonSize } from '@/enums/buttons';
import { ColorVariant } from '@/enums/colors';
import type { AuthObject } from '@/types/data-pagination';
import type {
    MissingMarksReportFilterOption,
    MissingMarksReportFilters,
    MissingMarksReportRow,
} from '@/types/assessments';
import type { BreadcrumbItemInterface } from '@/types/ui';
import { Head, router, useForm } from '@inertiajs/vue3';
import { trans } from 'laravel-vue-i18n';
import { computed, ref } from 'vue';

const props = defineProps<{
    rows: MissingMarksReportRow[];
    filters: MissingMarksReportFilters;
    filterOptions: {
        academicCalendars: MissingMarksReportFilterOption[];
        assessmentTypes: MissingMarksReportFilterOption[];
        departments: MissingMarksReportFilterOption[];
        lecturers: MissingMarksReportFilterOption[];
    };
    canExport: boolean;
    canEscalate: boolean;
    canRemind: boolean;
    auth: AuthObject;
}>();

const notes = ref('');

const breadcrumbs = computed<BreadcrumbItemInterface[]>(() => [
    { transKey: 'trans.dashboard', href: route('dashboard') },
    { title: trans('assessments.missing_marks_report_title') },
]);

const queryParams = computed(() => ({
    academic_calendar_id: props.filters.academicCalendarId ? String(props.filters.academicCalendarId) : undefined,
    assessment_type_id: props.filters.assessmentTypeId ? String(props.filters.assessmentTypeId) : undefined,
    institution_department_id: props.filters.departmentId ? String(props.filters.departmentId) : undefined,
    lecturer_staff_id: props.filters.lecturerStaffId ? String(props.filters.lecturerStaffId) : undefined,
}));

const exportUrl = computed(() => route('missing-marks-report.export', queryParams.value));

const applyFilter = (key: string, value: string): void => {
    router.get(
        route('missing-marks-report.index'),
        {
            ...queryParams.value,
            [key]: value || undefined,
        },
        { preserveState: true, replace: true },
    );
};

const escalateForm = useForm({
    assessment_calendar_id: 0,
    notes: '',
});

const remindForm = useForm({
    assessment_calendar_id: 0,
});

const escalate = (calendarId: number): void => {
    escalateForm.assessment_calendar_id = calendarId;
    escalateForm.notes = notes.value;
    escalateForm.post(route('missing-marks-report.escalate'), { preserveScroll: true });
};

const remind = (calendarId: number): void => {
    remindForm.assessment_calendar_id = calendarId;
    remindForm.post(route('missing-marks-report.remind'), { preserveScroll: true });
};
</script>

<template>
    <Head :title="$t('assessments.missing_marks_report_title')" />
    <PageContainer :breadcrumbs="breadcrumbs" :back-url="route('dashboard')">
        <div class="space-y-4">
            <div class="flex flex-wrap items-end justify-between gap-3">
                <div class="grid flex-1 grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-4">
                    <label class="flex flex-col gap-1 text-xs font-medium text-muted-foreground">
                        {{ $tChoice('academic_calendar.academic_calendar', 1) }}
                        <select
                            class="h-9 rounded-md border border-border bg-background px-2 text-sm text-foreground"
                            :value="filters.academicCalendarId ?? ''"
                            @change="applyFilter('academic_calendar_id', ($event.target as HTMLSelectElement).value)"
                        >
                            <option value="">{{ $t('trans.select_one') }}</option>
                            <option
                                v-for="option in filterOptions.academicCalendars"
                                :key="option.id"
                                :value="option.id"
                            >
                                {{ option.label }}
                            </option>
                        </select>
                    </label>
                    <label class="flex flex-col gap-1 text-xs font-medium text-muted-foreground">
                        {{ $tChoice('trans.assessment_type', 1) }}
                        <select
                            class="h-9 rounded-md border border-border bg-background px-2 text-sm text-foreground"
                            :value="filters.assessmentTypeId ?? ''"
                            @change="applyFilter('assessment_type_id', ($event.target as HTMLSelectElement).value)"
                        >
                            <option value="">{{ $t('trans.select_one') }}</option>
                            <option
                                v-for="option in filterOptions.assessmentTypes"
                                :key="option.id"
                                :value="option.id"
                            >
                                {{ option.label }}
                            </option>
                        </select>
                    </label>
                    <label class="flex flex-col gap-1 text-xs font-medium text-muted-foreground">
                        {{ $tChoice('trans.department', 1) }}
                        <select
                            class="h-9 rounded-md border border-border bg-background px-2 text-sm text-foreground"
                            :value="filters.departmentId ?? ''"
                            @change="applyFilter('institution_department_id', ($event.target as HTMLSelectElement).value)"
                        >
                            <option value="">{{ $t('trans.select_one') }}</option>
                            <option
                                v-for="option in filterOptions.departments"
                                :key="option.id"
                                :value="option.id"
                            >
                                {{ option.label }}
                            </option>
                        </select>
                    </label>
                    <label class="flex flex-col gap-1 text-xs font-medium text-muted-foreground">
                        {{ $t('assessments.missing_marks_lecturer') }}
                        <select
                            class="h-9 rounded-md border border-border bg-background px-2 text-sm text-foreground"
                            :value="filters.lecturerStaffId ?? ''"
                            @change="applyFilter('lecturer_staff_id', ($event.target as HTMLSelectElement).value)"
                        >
                            <option value="">{{ $t('trans.select_one') }}</option>
                            <option
                                v-for="option in filterOptions.lecturers"
                                :key="option.id"
                                :value="option.id"
                            >
                                {{ option.label }}
                            </option>
                        </select>
                    </label>
                </div>
                <a v-if="canExport" :href="exportUrl" class="inline-flex">
                    <BaseButton type="button" :variant="ColorVariant.primary_outline" :size="ButtonSize.xs">
                        {{ $t('assessments.missing_marks_export') }}
                    </BaseButton>
                </a>
            </div>

            <label v-if="canEscalate" class="flex max-w-xl flex-col gap-1 text-xs font-medium text-muted-foreground">
                {{ $t('assessments.missing_marks_notes') }}
                <textarea
                    v-model="notes"
                    rows="2"
                    class="rounded-md border border-border bg-background px-2 py-1.5 text-sm text-foreground"
                />
            </label>

            <div v-if="rows.length === 0" class="rounded-xl border border-border bg-card p-6">
                <Empty :message="$t('assessments.missing_marks_report_empty')" />
            </div>
            <div v-else class="overflow-x-auto rounded-xl border border-border bg-card">
                <table class="w-full min-w-[720px] table-fixed border-collapse text-left text-sm">
                    <thead>
                        <tr class="border-b border-border bg-muted/40">
                            <th class="px-3 py-2 font-medium">{{ $tChoice('trans.class', 1) }}</th>
                            <th class="px-3 py-2 font-medium">{{ $tChoice('trans.module', 1) }}</th>
                            <th class="px-3 py-2 font-medium">{{ $t('assessments.missing_marks_lecturer') }}</th>
                            <th class="px-3 py-2 font-medium">{{ $t('dashboard.academic_incomplete') }}</th>
                            <th class="px-3 py-2 font-medium">{{ $t('assessments.missing_marks_due_date') }}</th>
                            <th class="px-3 py-2 font-medium">{{ $t('assessments.missing_marks_last_tier') }}</th>
                            <th v-if="canEscalate || canRemind" class="px-3 py-2 font-medium" />
                        </tr>
                    </thead>
                    <tbody>
                        <tr
                            v-for="(row, index) in rows"
                            :key="`${row.assessmentCalendarId}-${row.className}-${row.moduleCode}-${index}`"
                            class="border-b border-border last:border-0"
                        >
                            <td class="px-3 py-2">{{ row.className }}</td>
                            <td class="px-3 py-2">
                                <div>{{ row.moduleName }}</div>
                                <div class="text-xs text-muted-foreground">{{ row.assessmentTypeName }}</div>
                            </td>
                            <td class="px-3 py-2">{{ row.lecturerNames }}</td>
                            <td class="px-3 py-2">{{ row.incompleteCount }}</td>
                            <td class="px-3 py-2">{{ row.dueDate ?? '—' }}</td>
                            <td class="px-3 py-2">{{ row.lastTierLabel ?? $t('assessments.missing_marks_none') }}</td>
                            <td v-if="canEscalate || canRemind" class="px-3 py-2">
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
                                        :processing="escalateForm.processing"
                                        @click="escalate(row.assessmentCalendarId)"
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
    </PageContainer>
</template>
