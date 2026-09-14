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
import { formatIsoDate } from '@/lib/departmentAssessmentCalendars';
import { buildFormOptions, clearFormErrors } from '@/lib/forms';
import type { CourseWorkProgressReportRow } from '@/types/course-work-progress';
import type { BreadcrumbItemInterface } from '@/types/ui';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { trans } from 'laravel-vue-i18n';
import { computed, ref } from 'vue';

defineProps<{
    status: 'awaiting' | 'all';
    reports: CourseWorkProgressReportRow[];
}>();

const breadcrumbs = computed<BreadcrumbItemInterface[]>(() => [
    { title: trans('dashboard.lecturer_dashboard_title'), href: route('dashboard') },
    { title: trans('academic_calendar.course_work_progress_reports_title') },
]);

const selected = ref<CourseWorkProgressReportRow | null>(null);
const form = useForm({ comment: '' });

const startAcknowledge = (report: CourseWorkProgressReportRow) => {
    selected.value = report;
    form.reset();
    form.clearErrors();
    openModal({ name: APP_MODULE_KEYS.course_work_progress_acknowledge });
};

const acknowledge = () => {
    if (!selected.value) {
        return;
    }

    form.post(
        route('course-work-progress-reports.acknowledge', { course_work_progress_report: selected.value.id }),
        buildFormOptions(
            form,
            trans('academic_calendar.course_work_progress_acknowledged'),
            trans('trans.item_save_failure', { item: trans('academic_calendar.course_work_progress_reports_title') }),
            APP_MODULE_KEYS.course_work_progress_acknowledge,
        ),
    );
};
</script>

<template>
    <Head :title="$t('academic_calendar.course_work_progress_reports_title')" />
    <PageContainer :breadcrumbs="breadcrumbs">
        <div class="space-y-4">
            <h1 class="text-lg font-semibold">{{ $t('academic_calendar.course_work_progress_reports_title') }}</h1>

            <nav :aria-label="$tChoice('trans.status', 1)" class="flex gap-2 text-sm">
                <Link
                    :href="route('course-work-progress-reports.index')"
                    :aria-current="status === 'awaiting' ? 'page' : undefined"
                    class="rounded-full border border-border px-3 py-1"
                    :class="status === 'awaiting' ? 'bg-primary text-primary-foreground' : 'hover:bg-muted'"
                >
                    {{ $t('academic_calendar.course_work_progress_awaiting_acknowledgement') }}
                </Link>
                <Link
                    :href="route('course-work-progress-reports.index', { status: 'all' })"
                    :aria-current="status === 'all' ? 'page' : undefined"
                    class="rounded-full border border-border px-3 py-1"
                    :class="status === 'all' ? 'bg-primary text-primary-foreground' : 'hover:bg-muted'"
                >
                    {{ $t('trans.all') }}
                </Link>
            </nav>

            <Empty v-if="reports.length === 0" :message="$t('academic_calendar.course_work_progress_reports_empty')" />

            <div v-else class="overflow-x-auto rounded-md border border-border">
                <table class="w-full text-left text-sm">
                    <caption class="sr-only">{{ $t('academic_calendar.course_work_progress_reports_title') }}</caption>
                    <thead>
                        <tr class="border-b border-border bg-muted/40 text-muted-foreground">
                            <th scope="col" class="px-3 py-2 font-medium">{{ $tChoice('trans.course', 1) }}</th>
                            <th scope="col" class="px-3 py-2 font-medium">{{ $t('academic_calendar.lecturer_in_charge') }}</th>
                            <th scope="col" class="px-3 py-2 font-medium">{{ $t('academic_calendar.course_work_progress_captured') }}</th>
                            <th scope="col" class="px-3 py-2 font-medium">{{ $tChoice('trans.status', 1) }}</th>
                            <th scope="col" class="px-3 py-2 text-right font-medium"><span class="sr-only">{{ $tChoice('trans.action', 2) }}</span></th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="report in reports" :key="report.id" class="border-b border-border/60 align-top last:border-0">
                            <th scope="row" class="px-3 py-2.5 font-medium">
                                <Link v-if="report.progressUrl" :href="report.progressUrl" class="text-primary hover:underline">{{ report.programme }}</Link>
                                <span class="block text-xs font-normal text-muted-foreground">{{ report.departmentName }}</span>
                            </th>
                            <td class="px-3 py-2.5">
                                {{ report.submitterName }}
                                <span class="block text-xs text-muted-foreground">{{ formatIsoDate(report.submittedAt) }}</span>
                                <span v-if="report.notes" class="mt-0.5 block max-w-md text-xs">{{ report.notes }}</span>
                            </td>
                            <td class="px-3 py-2.5">
                                <template v-if="report.totals">{{ report.totals.captured }} / {{ report.totals.expected }}</template>
                            </td>
                            <td class="px-3 py-2.5">
                                <template v-if="report.acknowledgedAt">
                                    {{ $t('academic_calendar.course_work_progress_acknowledged_by', { name: report.acknowledgerName, date: formatIsoDate(report.acknowledgedAt) }) }}
                                    <span v-if="report.hodComment" class="block text-xs text-muted-foreground">{{ report.hodComment }}</span>
                                </template>
                                <template v-else>{{ $t('academic_calendar.course_work_progress_awaiting_acknowledgement') }}</template>
                            </td>
                            <td class="px-3 py-2.5 text-right">
                                <BaseButton
                                    v-if="report.can?.acknowledge"
                                    type="button"
                                    :variant="ColorVariant.primary"
                                    :size="ButtonSize.xs"
                                    classes="rounded-full"
                                    @click="startAcknowledge(report)"
                                >
                                    {{ $t('academic_calendar.course_work_progress_acknowledge_action') }}
                                    <span class="sr-only">: {{ report.programme }}</span>
                                </BaseButton>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <BaseModal
            :name="APP_MODULE_KEYS.course_work_progress_acknowledge"
            :title="$t('academic_calendar.course_work_progress_acknowledge_action')"
            :on-form-action="() => acknowledge()"
            :form="form"
            :size="SizeVariant.md"
        >
            <template #body>
                <div class="space-y-1">
                    <p v-if="selected" class="text-sm font-medium">{{ selected.programme }}</p>
                    <label for="progress_ack_comment" class="text-sm font-medium">{{ $t('academic_calendar.course_work_progress_hod_comment_label') }}</label>
                    <textarea
                        id="progress_ack_comment"
                        v-model="form.comment"
                        rows="3"
                        maxlength="1000"
                        class="w-full rounded-md border border-input bg-background px-3 py-2 text-sm"
                        :aria-invalid="form.errors.comment ? 'true' : undefined"
                        aria-describedby="progress_ack_comment_error"
                        @input="clearFormErrors(form, 'comment')"
                    />
                    <p v-if="form.errors.comment" id="progress_ack_comment_error" role="alert" class="text-sm text-destructive">{{ form.errors.comment }}</p>
                </div>
            </template>
        </BaseModal>
    </PageContainer>
</template>
