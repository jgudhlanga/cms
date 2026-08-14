<script setup lang="ts">
import BaseAlert from '@/components/core/alert/BaseAlert.vue';
import { BaseButton } from '@/components/core/button';
import PageContainer from '@/components/core/page/PageContainer.vue';
import InvalidIdNumberBanner from '@/components/students/profile/InvalidIdNumberBanner.vue';
import { ButtonSize } from '@/enums/buttons';
import { ColorVariant } from '@/enums/colors';
import { TypeVariant } from '@/enums/type-variants';
import {
    formatFeeClearanceUsd,
    formatFeeClearanceZwgAmount,
    type FeeClearanceBankConversion,
} from '@/lib/feeClearanceMoney';
import { AuthObject } from '@/types/data-pagination';
import { Student } from '@/types/students';
import { BreadcrumbItemInterface } from '@/types/ui';
import { Head, Link } from '@inertiajs/vue3';
import { Printer } from '@lucide/vue';
import { computed } from 'vue';

type AccessPayload = {
    canViewResults: boolean;
    gate: 'clearance' | 'fees' | 'apprentice' | 'sponsored' | 'not_enrolled' | 'non_hexco';
    allowOnlineClearance: boolean;
    fees: {
        expectedTotal: number;
        paidFromBank?: number;
        paidFromLedger?: number;
        paidTotal: number;
        outstanding: number;
        isFullyPaid: boolean;
        breakdown: Array<{ key: string; label: string; amount: number }>;
        hasStudentNumber: boolean;
        bankConversions?: FeeClearanceBankConversion[];
    } | null;
    clearance: {
        isFullyCleared: boolean;
        sections: Array<{ key: string; label: string; cleared: boolean }>;
    } | null;
    idValidation: { required: boolean; isValid: boolean; needsCorrection: boolean };
};

type SubjectRow = {
    id: number;
    subjectCode: string | null;
    subject: string | null;
    grade: string | null;
    session: string | null;
    courseComment: string | null;
};

type Summary = {
    id: number;
    candidateNumber: string;
    calendarYear: number;
    session: string;
    comment: string | null;
    rawCourseComment: string | null;
};

const props = defineProps<{
    student: Student;
    access: AccessPayload;
    allowed: boolean;
    summary: Summary;
    subjects: SubjectRow[];
    logBookFeeGapNotice: string | null;
    auth: AuthObject;
}>();

const breadcrumbs: BreadcrumbItemInterface[] = [
    { title: props.auth.user.attributes?.name },
    { transKey: 'exam_results', href: route('portal.exam-results') },
    { title: props.summary.session },
];

const money = formatFeeClearanceUsd;
const bankConversions = () => props.access.fees?.bankConversions ?? [];
const tuitionPaymentRoute = () => route('portal.profile.financials.pay', { returnTo: 'exam-results' });
const canPayOutstanding = () =>
    props.access.gate === 'fees'
    && !!props.access.fees?.hasStudentNumber
    && Number(props.access.fees?.outstanding ?? 0) > 0;

const SUCCESS_COMMENTS = new Set(['AWARD', 'PROCEED']);
const NEGATIVE_COMMENTS = new Set(['ABSENT', 'DEFERRED', 'DISQUALIFIED', 'REFERRED']);

const COMMENT_CHIP_BASE =
    'inline-flex rounded-md px-1.5 py-0.5 text-[11px] font-semibold uppercase tracking-wide';

const normalizeCourseComment = (value: string | null | undefined): string | null => {
    const normalized = value?.trim();
    return normalized ? normalized : null;
};

const courseCommentToneClass = (value: string | null | undefined): string => {
    const normalized = normalizeCourseComment(value)?.toUpperCase();
    if (!normalized) {
        return 'bg-muted text-foreground';
    }
    if (SUCCESS_COMMENTS.has(normalized)) {
        return 'bg-emerald-100 text-emerald-900 dark:bg-emerald-950 dark:text-emerald-100';
    }
    if (NEGATIVE_COMMENTS.has(normalized)) {
        return 'bg-red-100 text-red-900 dark:bg-red-950 dark:text-red-100';
    }
    return 'bg-muted text-foreground';
};

const summaryComment = computed(() => normalizeCourseComment(props.summary.comment));

const subjectRows = computed(() =>
    props.subjects.map((row) => ({
        ...row,
        displayComment: normalizeCourseComment(row.courseComment),
    })),
);

const printStatement = () => {
    window.print();
};
</script>

<template>
    <Head :title="`${$t('examinations.exam_results_title')} — ${summary.session}`" />
    <PageContainer :breadcrumbs="breadcrumbs" :back-url="route('portal.exam-results')">
        <div class="print:hidden mb-3 space-y-3">
            <InvalidIdNumberBanner :student="student" />

            <BaseAlert
                v-if="access.idValidation.needsCorrection"
                :type="TypeVariant.danger"
                :description="$t('trans.exam_results_id_invalid_banner')"
            />

            <BaseAlert
                v-if="access.gate === 'not_enrolled'"
                :type="TypeVariant.warning"
                :description="$t('trans.exam_results_not_enrolled')"
            />

            <BaseAlert
                v-else-if="access.gate === 'non_hexco'"
                :type="TypeVariant.warning"
                :description="$t('trans.exam_results_non_hexco')"
            />

            <BaseAlert
                v-else-if="access.gate === 'apprentice'"
                :type="TypeVariant.success"
                :description="$t('trans.exam_results_apprentice_exempt')"
            />

            <BaseAlert
                v-else-if="access.gate === 'sponsored'"
                :type="TypeVariant.success"
                :description="$t('trans.exam_results_sponsored_exempt')"
            />

            <template v-if="!allowed">
                <BaseAlert
                    v-if="access.gate !== 'not_enrolled' && access.gate !== 'non_hexco'"
                    :type="TypeVariant.warning"
                    :description="$t('trans.exam_results_access_denied')"
                />

                <div
                    v-if="access.gate === 'fees' && access.fees"
                    class="space-y-1.5 rounded-lg border border-border bg-card p-3 text-sm"
                >
                    <p class="font-medium">{{ $t('trans.exam_results_fee_status') }}</p>
                    <ul class="space-y-1">
                        <li
                            v-for="item in access.fees.breakdown"
                            :key="item.key"
                            class="flex justify-between gap-4"
                        >
                            <span>{{ item.label }}</span>
                            <span class="font-mono">{{ money(item.amount) }}</span>
                        </li>
                        <li class="space-y-1">
                            <div class="flex justify-between gap-4">
                                <span>{{ $t('trans.paid_from_bank') }}</span>
                                <span class="font-mono">{{ money(access.fees.paidFromBank ?? 0) }}</span>
                            </div>
                            <p
                                v-for="conversion in bankConversions()"
                                :key="`${conversion.rate}-${conversion.date}`"
                                class="text-xs text-muted-foreground"
                            >
                                {{ $t('trans.fee_clearance_zwg_conversion_note', {
                                    amount: formatFeeClearanceZwgAmount(conversion.originalAmount),
                                    label: conversion.label,
                                    date: conversion.date,
                                }) }}
                            </p>
                        </li>
                        <li class="flex justify-between gap-4">
                            <span>{{ $t('trans.paid_online') }}</span>
                            <span class="font-mono">{{ money(access.fees.paidFromLedger ?? 0) }}</span>
                        </li>
                        <li class="flex justify-between gap-4">
                            <span>{{ $t('trans.exam_results_paid_total') }}</span>
                            <span class="font-mono">{{ money(access.fees.paidTotal) }}</span>
                        </li>
                        <li class="flex justify-between gap-4 border-t pt-1.5 font-medium">
                            <span>{{ $t('trans.exam_results_outstanding') }}</span>
                            <span class="font-mono">{{ money(access.fees.outstanding) }}</span>
                        </li>
                    </ul>
                    <BaseAlert
                        v-if="canPayOutstanding()"
                        :type="TypeVariant.info"
                        :title="$t('trans.tuition_fee_payment_disclaimer_title')"
                        :description="$t('trans.tuition_fee_accounts_disclaimer')"
                    />
                    <Link v-if="canPayOutstanding()" :href="tuitionPaymentRoute()" class="inline-flex">
                        <BaseButton type="button">{{ $t('trans.pay_now') }}</BaseButton>
                    </Link>
                    <p v-if="logBookFeeGapNotice" class="text-xs text-muted-foreground">{{ logBookFeeGapNotice }}</p>
                </div>

                <div
                    v-else-if="access.gate === 'clearance' && access.clearance"
                    class="space-y-1.5 rounded-lg border border-border bg-card p-3"
                >
                    <p class="text-sm font-medium">{{ $t('trans.exam_results_pending_offices') }}</p>
                    <ul class="space-y-1.5 text-sm">
                        <li
                            v-for="section in access.clearance.sections"
                            :key="section.key"
                            class="flex items-center justify-between gap-3"
                        >
                            <span>{{ section.label }}</span>
                            <span
                                class="rounded-full px-2 py-0.5 text-xs font-medium"
                                :class="section.cleared ? 'bg-emerald-100 text-emerald-800' : 'bg-amber-100 text-amber-900'"
                            >
                                {{ section.cleared ? $t('trans.clearance_cleared') : $t('trans.clearance_pending') }}
                            </span>
                        </li>
                    </ul>
                </div>

                <Link :href="route('portal.exam-results')" class="inline-block text-sm font-medium text-primary underline-offset-2 hover:underline">
                    {{ $t('examinations.exam_results_back_to_list') }}
                </Link>
            </template>

            <div v-else class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                <div class="min-w-0">
                    <h2 class="text-base font-semibold leading-tight">{{ summary.session }}</h2>
                    <div class="mt-0.5 flex flex-wrap items-center gap-x-2 gap-y-1 text-sm text-muted-foreground">
                        <span class="wrap-break-word">{{ summary.candidateNumber }} · {{ summary.calendarYear }}</span>
                        <span
                            v-if="summaryComment"
                            :class="[COMMENT_CHIP_BASE, courseCommentToneClass(summaryComment)]"
                        >
                            {{ summaryComment }}
                        </span>
                    </div>
                </div>
                <BaseButton
                    type="button"
                    :size="ButtonSize.sm"
                    :variant="ColorVariant.danger"
                    class="w-full shrink-0 sm:w-auto"
                    @click="printStatement"
                >
                    <Printer class="h-3.5 w-3.5" aria-hidden="true" />
                    {{ $t('finance.print_statement') }}
                </BaseButton>
            </div>
        </div>

        <div v-if="allowed" class="exam-results-statement">
            <p class="mb-3 text-xs font-medium text-red-600 print:mb-2 print:text-red-600">
                {{ $t('examinations.exam_results_unofficial_use') }}
            </p>

            <div class="mb-3 hidden rounded-lg border border-black p-3 print:block">
                <h1 class="text-base font-bold">{{ $t('examinations.exam_results_title') }}</h1>
                <p class="text-sm">
                    {{ summary.candidateNumber }} · {{ summary.session }} · {{ summary.calendarYear }}
                </p>
                <p class="mt-2 text-xs font-medium text-red-600 print:text-red-600">
                    {{ $t('examinations.exam_results_unofficial_use') }}
                </p>
                <p v-if="summaryComment" class="mt-1">
                    <span class="text-[11px] text-muted-foreground">{{ $t('examinations.course_comment') }}:</span>
                    <span
                        class="ml-1.5"
                        :class="[COMMENT_CHIP_BASE, courseCommentToneClass(summaryComment)]"
                    >
                        {{ summaryComment }}
                    </span>
                </p>
            </div>

            <div
                v-if="subjectRows.length > 0"
                class="overflow-hidden rounded-lg border border-border bg-card text-card-foreground print:border-black"
            >
                <div
                    class="flex items-center justify-between gap-3 border-b border-border px-3 py-1.5 print:border-black"
                >
                    <span class="text-[11px] font-semibold uppercase tracking-wide text-muted-foreground">
                        {{ $t('examinations.subject') }}
                    </span>
                    <span class="text-[11px] font-semibold uppercase tracking-wide text-muted-foreground">
                        {{ $t('examinations.grade') }}
                    </span>
                </div>

                <ul class="divide-y divide-border print:divide-black">
                    <li
                        v-for="row in subjectRows"
                        :key="row.id"
                        class="flex items-center gap-3 px-3 py-2"
                    >
                        <div class="min-w-0 flex-1">
                            <div class="flex min-w-0 flex-wrap items-baseline gap-x-2 gap-y-0.5">
                                <p v-if="row.subjectCode" class="font-mono text-xs text-muted-foreground">
                                    {{ row.subjectCode }}
                                </p>
                                <p class="text-sm font-medium leading-snug">
                                    {{ row.subject || $t('examinations.subject') }}
                                </p>
                            </div>
                            <div class="mt-0.5 flex flex-wrap items-center gap-x-1.5 gap-y-1">
                                <span class="text-[11px] text-muted-foreground">
                                    {{ row.session || '—' }}
                                </span>
                                <span
                                    v-if="row.displayComment"
                                    :class="[COMMENT_CHIP_BASE, courseCommentToneClass(row.displayComment)]"
                                >
                                    {{ row.displayComment }}
                                </span>
                            </div>
                        </div>
                        <span
                            class="inline-flex min-w-10 shrink-0 items-center justify-center rounded-md bg-muted px-2 py-1 text-center text-sm font-semibold tabular-nums print:border print:border-black print:bg-transparent"
                        >
                            {{ row.grade || '—' }}
                        </span>
                    </li>
                </ul>
            </div>

            <div
                v-else
                class="rounded-lg border border-dashed border-border p-4 text-center text-sm text-muted-foreground"
            >
                {{ $t('examinations.no_results') }}
            </div>
        </div>
    </PageContainer>
</template>

<style>
@media print {
    body * {
        visibility: hidden;
    }
    .exam-results-statement,
    .exam-results-statement * {
        visibility: visible;
    }
    .exam-results-statement {
        position: absolute;
        left: 0;
        top: 0;
        width: 100%;
    }
}
</style>
