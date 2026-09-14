<script setup lang="ts">
import { BaseButton } from '@/components/core/button';
import BaseModal from '@/components/core/modal/BaseModal.vue';
import PageContainer from '@/components/core/page/PageContainer.vue';
import Empty from '@/components/core/util/Empty.vue';
import { ButtonSize } from '@/enums/buttons';
import { ColorVariant } from '@/enums/colors';
import { SizeVariant } from '@/enums/sizes';
import { openModal } from '@/lib/alerts';
import { APP_MODULE_KEYS } from '@/lib/constants';
import { formatIsoDate, formatIsoDateRange, windowStatusBadgeClass } from '@/lib/departmentAssessmentCalendars';
import { buildFormOptions, clearFormErrors } from '@/lib/forms';
import type { CourseWorkProgress, CourseWorkProgressReportRow } from '@/types/course-work-progress';
import type { AssessmentWindowStatus } from '@/types/department-assessment-calendars';
import type { BreadcrumbItemInterface } from '@/types/ui';
import { Head, useForm } from '@inertiajs/vue3';
import { trans } from 'laravel-vue-i18n';
import { computed } from 'vue';

const props = defineProps<{
    progress: CourseWorkProgress;
    isHistorical: boolean;
    canSubmitReport: boolean;
    reports: CourseWorkProgressReportRow[];
}>();

// Return to the same year's list when looking back at an earlier year.
const indexUrl = computed((): string =>
    props.isHistorical
        ? route('teaching.course-work-progress.index', { calendar_year: props.progress.calendarYear })
        : route('teaching.course-work-progress.index'),
);

const breadcrumbs = computed<BreadcrumbItemInterface[]>(() => [
    { title: trans('dashboard.lecturer_dashboard_title'), href: route('dashboard') },
    { title: trans('academic_calendar.course_work_progress_title'), href: indexUrl.value },
    { title: props.progress.programme },
]);

const assessmentName = (assessmentTypeId: number | null): string =>
    assessmentTypeId === null
        ? trans('academic_calendar.course_work_window_module_mark')
        : (props.progress.assessments.find((assessment) => assessment.assessmentTypeId === assessmentTypeId)?.assessmentTypeName ?? '');

const formatDateTime = (value: string | null): string =>
    value ? new Date(value).toLocaleString('en-GB', { day: '2-digit', month: 'short', year: 'numeric', hour: '2-digit', minute: '2-digit' }) : '';

const form = useForm({ notes: '' });

const submitReport = () => {
    form.post(
        route('teaching.course-work-progress.reports.store', { class_config: props.progress.classConfigId }),
        buildFormOptions(
            form,
            trans('academic_calendar.course_work_progress_submitted'),
            trans('trans.item_save_failure', { item: trans('academic_calendar.course_work_progress_reports_title') }),
            APP_MODULE_KEYS.course_work_progress_report,
        ),
    );
};
</script>

<template>
    <Head :title="progress.programme" />
    <PageContainer :breadcrumbs="breadcrumbs" :back-url="indexUrl">
        <div class="space-y-5">
            <div class="flex flex-wrap items-start justify-between gap-3">
                <div>
                    <h1 class="text-lg font-semibold">{{ progress.programme }}</h1>
                    <p class="text-sm text-muted-foreground">
                        {{ progress.departmentName }} ·
                        {{ $t('academic_calendar.lecturer_in_charge') }}:
                        {{ progress.lecturerInCharge?.name ?? $t('academic_calendar.lecturer_in_charge_not_assigned') }}
                    </p>
                    <p v-if="progress.lastReport?.submittedAt" class="text-xs text-muted-foreground">
                        {{ $t('academic_calendar.course_work_progress_last_report', { date: formatDateTime(progress.lastReport.submittedAt) }) }}
                    </p>
                </div>
                <BaseButton
                    v-if="canSubmitReport"
                    type="button"
                    :variant="ColorVariant.primary"
                    :size="ButtonSize.sm"
                    classes="rounded-full"
                    @click="openModal({ name: APP_MODULE_KEYS.course_work_progress_report })"
                >
                    {{ $t('academic_calendar.course_work_progress_submit_action') }}
                </BaseButton>
            </div>

            <p
                v-if="isHistorical"
                role="status"
                class="rounded-md border border-amber-300 bg-amber-50 px-3 py-2 text-sm text-amber-900 dark:border-amber-800 dark:bg-amber-950/40 dark:text-amber-200"
            >
                {{ $t('academic_calendar.course_work_progress_historical_notice', { year: progress.calendarYear }) }}
            </p>

            <dl class="grid grid-cols-2 gap-3 sm:grid-cols-4">
                <div class="rounded-md border border-border p-3">
                    <dt class="text-xs text-muted-foreground">{{ $t('academic_calendar.course_work_progress_expected') }}</dt>
                    <dd class="text-lg font-semibold">{{ progress.totals.expected }}</dd>
                </div>
                <div class="rounded-md border border-border p-3">
                    <dt class="text-xs text-muted-foreground">{{ $t('academic_calendar.course_work_progress_captured') }}</dt>
                    <dd class="text-lg font-semibold">{{ progress.totals.captured }}</dd>
                </div>
                <div class="rounded-md border border-border p-3">
                    <dt class="text-xs text-muted-foreground">{{ $t('academic_calendar.course_work_progress_missing') }}</dt>
                    <dd class="text-lg font-semibold">{{ progress.totals.missing }}</dd>
                </div>
                <div class="rounded-md border border-border p-3">
                    <dt class="text-xs text-muted-foreground">%</dt>
                    <dd class="text-lg font-semibold">
                        {{ progress.totals.percent === null || progress.totals.percent === undefined ? '—' : `${progress.totals.percent}%` }}
                    </dd>
                </div>
            </dl>

            <section :aria-labelledby="'progress-windows-heading'" class="space-y-2">
                <h2 id="progress-windows-heading" class="text-sm font-semibold">{{ $t('academic_calendar.department_assessment_calendar_effective_window') }}</h2>
                <ul class="flex flex-wrap gap-2">
                    <li v-for="assessment in progress.assessments" :key="assessment.assessmentTypeId ?? 'module-mark'" class="rounded-md border border-border px-3 py-2 text-sm">
                        <span class="font-medium">{{ assessment.assessmentTypeName }}</span>
                        <span :class="windowStatusBadgeClass(assessment.status as AssessmentWindowStatus)" class="ml-2 inline-flex rounded-full px-2 py-0.5 text-xs font-medium">
                            {{ assessment.statusLabel }}
                        </span>
                        <span class="block text-xs text-muted-foreground">{{ formatIsoDateRange(assessment.startDate, assessment.endDate) }}</span>
                    </li>
                </ul>
            </section>

            <Empty v-if="progress.classes.length === 0" :message="$t('academic_calendar.course_work_progress_empty')" />

            <section v-for="academicClass in progress.classes" :key="academicClass.id" class="space-y-2" :aria-labelledby="`progress-class-${academicClass.id}`">
                <h2 :id="`progress-class-${academicClass.id}`" class="text-sm font-semibold">
                    {{ academicClass.name }}
                    <span class="font-normal text-muted-foreground">· {{ $tChoice('trans.student', 2) }}: {{ academicClass.studentCount }}</span>
                </h2>
                <div class="overflow-x-auto rounded-md border border-border">
                    <table class="w-full text-left text-sm">
                        <caption class="sr-only">{{ academicClass.name }} · {{ $t('academic_calendar.course_work_progress_title') }}</caption>
                        <thead>
                            <tr class="border-b border-border bg-muted/40 text-muted-foreground">
                                <th scope="col" class="px-3 py-2 font-medium">{{ $tChoice('trans.module', 1) }}</th>
                                <th scope="col" class="px-3 py-2 font-medium">{{ $tChoice('syllabus.lecturer', 2) }}</th>
                                <th scope="col" class="px-3 py-2 font-medium">{{ $t('academic_calendar.course_work_progress_captured') }}</th>
                                <th scope="col" class="px-3 py-2 font-medium">{{ $t('academic_calendar.course_work_progress_missing') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="module in academicClass.modules" :key="module.id" class="border-b border-border/60 align-top last:border-0">
                                <th scope="row" class="px-3 py-2.5 font-medium">
                                    {{ module.code ? `${module.code} — ${module.title}` : module.title }}
                                </th>
                                <td class="px-3 py-2.5">{{ module.lecturers.length ? module.lecturers.join(', ') : '—' }}</td>
                                <td class="px-3 py-2.5">
                                    {{ module.captured }} / {{ module.expected }}
                                    <ul class="mt-1 space-y-0.5 text-xs text-muted-foreground">
                                        <li v-for="item in module.byAssessment" :key="item.assessmentTypeId ?? 'module-mark'">
                                            {{ assessmentName(item.assessmentTypeId) }}: {{ item.captured }} / {{ item.expected }}
                                        </li>
                                    </ul>
                                </td>
                                <td class="px-3 py-2.5" :class="module.missing > 0 ? 'font-semibold text-rose-700 dark:text-rose-300' : ''">
                                    {{ module.missing }}
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </section>

            <section v-if="reports.length > 0" class="space-y-2" aria-labelledby="progress-reports-heading">
                <h2 id="progress-reports-heading" class="text-sm font-semibold">{{ $t('academic_calendar.course_work_progress_reports_title') }}</h2>
                <ul class="space-y-2">
                    <li v-for="report in reports" :key="report.id" class="rounded-md border border-border p-3 text-sm">
                        <p class="font-medium">{{ formatDateTime(report.submittedAt) }} · {{ report.submitterName }}</p>
                        <p v-if="report.totals" class="text-xs text-muted-foreground">{{ report.totals.captured }} / {{ report.totals.expected }}</p>
                        <p v-if="report.notes" class="mt-1">{{ report.notes }}</p>
                        <p class="mt-1 text-xs text-muted-foreground">
                            <template v-if="report.acknowledgedAt">
                                {{ $t('academic_calendar.course_work_progress_acknowledged_by', { name: report.acknowledgerName, date: formatIsoDate(report.acknowledgedAt) }) }}
                                <template v-if="report.hodComment"> — {{ report.hodComment }}</template>
                            </template>
                            <template v-else>{{ $t('academic_calendar.course_work_progress_awaiting_acknowledgement') }}</template>
                        </p>
                    </li>
                </ul>
            </section>
        </div>

        <BaseModal
            v-if="canSubmitReport"
            :name="APP_MODULE_KEYS.course_work_progress_report"
            :title="$t('academic_calendar.course_work_progress_submit_title')"
            :on-form-action="() => submitReport()"
            :form="form"
            :size="SizeVariant.md"
        >
            <template #body>
                <div class="space-y-1">
                    <label for="progress_report_notes" class="text-sm font-medium">{{ $t('academic_calendar.course_work_progress_notes_label') }}</label>
                    <textarea
                        id="progress_report_notes"
                        v-model="form.notes"
                        rows="4"
                        maxlength="2000"
                        class="w-full rounded-md border border-input bg-background px-3 py-2 text-sm"
                        :aria-invalid="form.errors.notes ? 'true' : undefined"
                        aria-describedby="progress_report_notes_error"
                        @input="clearFormErrors(form, 'notes')"
                    />
                    <p v-if="form.errors.notes" id="progress_report_notes_error" role="alert" class="text-sm text-destructive">{{ form.errors.notes }}</p>
                </div>
            </template>
        </BaseModal>
    </PageContainer>
</template>
