<script setup lang="ts">
import { BaseButton } from '@/components/core/button';
import PageContainer from '@/components/core/page/PageContainer.vue';
import Empty from '@/components/core/util/Empty.vue';
import { ButtonSize } from '@/enums/buttons';
import { ColorVariant } from '@/enums/colors';
import { openModal } from '@/lib/alerts';
import { APP_MODULE_KEYS } from '@/lib/constants';
import { formatIsoDate } from '@/lib/departmentAssessmentCalendars';
import type {
    CourseWorkExtensionDecisionMode,
    CourseWorkExtensionRow,
    CourseWorkExtensionStatus,
} from '@/types/course-work-extensions';
import type { BreadcrumbItemInterface } from '@/types/ui';
import { Head, Link, router } from '@inertiajs/vue3';
import { trans } from 'laravel-vue-i18n';
import { computed } from 'vue';
import DecisionModal from './partials/DecisionModal.vue';

const props = defineProps<{
    extensions: CourseWorkExtensionRow[];
    status: CourseWorkExtensionStatus | 'all';
    statuses: Array<{ value: CourseWorkExtensionStatus; label: string }>;
    isApprover: boolean;
    maxExtensionDays: number;
}>();

const breadcrumbs = computed<BreadcrumbItemInterface[]>(() => [
    { title: trans('dashboard.lecturer_dashboard_title'), href: route('dashboard') },
    { title: trans('academic_calendar.course_work_extensions_title') },
]);

const statusFilters = computed(() => [
    { value: 'all', label: trans('trans.all') },
    ...props.statuses,
]);

const statusBadgeClass = (status: CourseWorkExtensionStatus): string => {
    switch (status) {
        case 'approved':
            return 'bg-emerald-100 text-emerald-800 dark:bg-emerald-900/40 dark:text-emerald-200';
        case 'pending':
            return 'bg-amber-100 text-amber-800 dark:bg-amber-900/40 dark:text-amber-200';
        case 'rejected':
        case 'revoked':
            return 'bg-rose-100 text-rose-800 dark:bg-rose-900/40 dark:text-rose-200';
        default:
            return 'bg-muted text-muted-foreground';
    }
};

const decide = (extension: CourseWorkExtensionRow, mode: CourseWorkExtensionDecisionMode) => {
    openModal({ name: APP_MODULE_KEYS.course_work_extension_decision, edit: { extension, mode } });
};

const cancel = (extension: CourseWorkExtensionRow) => {
    router.post(route('course-work-extensions.cancel', { course_work_capture_extension: extension.id }), {}, { preserveScroll: true });
};
</script>

<template>
    <Head :title="$t('academic_calendar.course_work_extensions_title')" />
    <PageContainer :breadcrumbs="breadcrumbs">
        <div class="space-y-4">
            <h1 class="text-lg font-semibold">{{ $t('academic_calendar.course_work_extensions_title') }}</h1>

            <nav :aria-label="$tChoice('trans.status', 1)" class="flex flex-wrap gap-2 text-sm">
                <Link
                    v-for="filter in statusFilters"
                    :key="filter.value"
                    :href="route('course-work-extensions.index', { status: filter.value })"
                    :aria-current="filter.value === status ? 'page' : undefined"
                    class="rounded-full border border-border px-3 py-1"
                    :class="filter.value === status ? 'bg-primary text-primary-foreground' : 'text-foreground hover:bg-muted'"
                    preserve-scroll
                >
                    {{ filter.label }}
                </Link>
            </nav>

            <Empty v-if="extensions.length === 0" :message="$t('academic_calendar.course_work_extension_empty')" />

            <div v-else class="overflow-x-auto rounded-md border border-border">
                <table class="w-full text-left text-sm">
                    <caption class="sr-only">{{ $t('academic_calendar.course_work_extensions_title') }}</caption>
                    <thead>
                        <tr class="border-b border-border bg-muted/40 text-muted-foreground">
                            <th scope="col" class="px-3 py-2 font-medium">{{ $tChoice('trans.module', 1) }}</th>
                            <th scope="col" class="px-3 py-2 font-medium">
                                {{ $t('academic_calendar.course_work_extension_requester_column') }}
                            </th>
                            <th scope="col" class="px-3 py-2 font-medium">
                                {{ $t('academic_calendar.course_work_extension_requested_until_column') }}
                            </th>
                            <th scope="col" class="px-3 py-2 font-medium">{{ $tChoice('trans.status', 1) }}</th>
                            <th scope="col" class="px-3 py-2 text-right font-medium">
                                <span class="sr-only">{{ $tChoice('trans.action', 2) }}</span>
                            </th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="extension in extensions" :key="extension.id" class="border-b border-border/60 align-top last:border-0">
                            <th scope="row" class="px-3 py-2.5 font-medium text-foreground">
                                {{ extension.assessmentName }}
                                <span class="block text-xs font-normal text-muted-foreground">
                                    {{ extension.moduleName }} · {{ extension.className }}
                                    <template v-if="extension.departmentName"> · {{ extension.departmentName }}</template>
                                </span>
                            </th>
                            <td class="px-3 py-2.5">
                                {{ extension.requesterName }}
                                <span class="mt-0.5 block max-w-md text-xs text-muted-foreground">{{ extension.reason }}</span>
                            </td>
                            <td class="px-3 py-2.5">
                                {{ formatIsoDate(extension.requestedUntil) }}
                                <span v-if="extension.globalEndDate" class="mt-0.5 block text-xs text-muted-foreground">
                                    {{ $t('academic_calendar.course_work_extension_college_deadline', { date: formatIsoDate(extension.globalEndDate) }) }}
                                </span>
                            </td>
                            <td class="px-3 py-2.5">
                                <span :class="statusBadgeClass(extension.status)" class="inline-flex rounded-full px-2 py-0.5 text-xs font-medium">
                                    {{ extension.statusLabel }}
                                </span>
                                <span v-if="extension.approvedUntil" class="mt-0.5 block text-xs text-muted-foreground">
                                    {{ $t('academic_calendar.course_work_extension_approved_until_column') }}: {{ formatIsoDate(extension.approvedUntil) }}
                                </span>
                                <span v-if="extension.decisionNote" class="mt-0.5 block text-xs text-muted-foreground">
                                    {{ extension.deciderName }}: {{ extension.decisionNote }}
                                </span>
                            </td>
                            <td class="px-3 py-2.5">
                                <div class="flex flex-wrap justify-end gap-2">
                                    <BaseButton
                                        v-if="extension.can.approve"
                                        type="button"
                                        :variant="ColorVariant.primary"
                                        :size="ButtonSize.xs"
                                        classes="rounded-full"
                                        @click="decide(extension, 'approve')"
                                    >
                                        {{ $t('academic_calendar.course_work_extension_approve_action') }}
                                        <span class="sr-only">: {{ extension.assessmentName }}, {{ extension.className }}</span>
                                    </BaseButton>
                                    <BaseButton
                                        v-if="extension.can.reject"
                                        type="button"
                                        :variant="ColorVariant.primary_outline"
                                        :size="ButtonSize.xs"
                                        classes="rounded-full"
                                        @click="decide(extension, 'reject')"
                                    >
                                        {{ $t('academic_calendar.course_work_extension_reject_action') }}
                                        <span class="sr-only">: {{ extension.assessmentName }}, {{ extension.className }}</span>
                                    </BaseButton>
                                    <BaseButton
                                        v-if="extension.can.revoke"
                                        type="button"
                                        :variant="ColorVariant.primary_outline"
                                        :size="ButtonSize.xs"
                                        classes="rounded-full"
                                        @click="decide(extension, 'revoke')"
                                    >
                                        {{ $t('academic_calendar.course_work_extension_revoke_action') }}
                                        <span class="sr-only">: {{ extension.assessmentName }}, {{ extension.className }}</span>
                                    </BaseButton>
                                    <BaseButton
                                        v-if="extension.can.cancel"
                                        type="button"
                                        :variant="ColorVariant.primary_outline"
                                        :size="ButtonSize.xs"
                                        classes="rounded-full"
                                        @click="cancel(extension)"
                                    >
                                        {{ $t('academic_calendar.course_work_extension_cancel_action') }}
                                        <span class="sr-only">: {{ extension.assessmentName }}, {{ extension.className }}</span>
                                    </BaseButton>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <DecisionModal />
    </PageContainer>
</template>
