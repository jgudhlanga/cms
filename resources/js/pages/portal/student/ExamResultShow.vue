<script setup lang="ts">
import BaseAlert from '@/components/core/alert/BaseAlert.vue';
import { BaseButton } from '@/components/core/button';
import PageContainer from '@/components/core/page/PageContainer.vue';
import InvalidIdNumberBanner from '@/components/students/profile/InvalidIdNumberBanner.vue';
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

type AccessPayload = {
    canViewResults: boolean;
    gate: 'clearance' | 'fees';
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

const printStatement = () => {
    window.print();
};
</script>

<template>
    <Head :title="`${$t('examinations.exam_results_title')} — ${summary.session}`" />
    <PageContainer :breadcrumbs="breadcrumbs" :back-url="route('portal.exam-results')">
        <div class="print:hidden space-y-4 mb-4">
            <InvalidIdNumberBanner :student="student" />

            <BaseAlert
                v-if="access.idValidation.needsCorrection"
                :type="TypeVariant.danger"
                :description="$t('trans.exam_results_id_invalid_banner')"
            />

            <template v-if="!allowed">
                <BaseAlert
                    :type="TypeVariant.warning"
                    :description="$t('trans.exam_results_access_denied')"
                />

                <div v-if="access.gate === 'fees' && access.fees" class="rounded-xl border border-border bg-card p-4 space-y-2 text-sm">
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
                        <li class="flex justify-between gap-4 border-t pt-2 font-medium">
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

                <div v-else-if="access.gate === 'clearance' && access.clearance" class="rounded-xl border border-border bg-card p-4 space-y-2">
                    <p class="text-sm font-medium">{{ $t('trans.exam_results_pending_offices') }}</p>
                    <ul class="space-y-2 text-sm">
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

            <div v-else class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                <div class="min-w-0">
                    <h2 class="text-lg font-semibold leading-tight">{{ summary.session }}</h2>
                    <p class="mt-1 text-sm text-muted-foreground wrap-break-word">
                        {{ summary.candidateNumber }} · {{ summary.calendarYear }}
                        <span v-if="summary.comment"> · {{ summary.comment }}</span>
                    </p>
                </div>
                <BaseButton type="button" class="w-full sm:w-auto" @click="printStatement">
                    {{ $t('examinations.exam_results_print') }}
                </BaseButton>
            </div>
        </div>

        <div v-if="allowed" class="exam-results-statement space-y-3">
            <div class="hidden print:block rounded-xl border border-black p-4">
                <h1 class="text-lg font-bold">{{ $t('examinations.exam_results_title') }}</h1>
                <p class="text-sm">
                    {{ summary.candidateNumber }} · {{ summary.session }} · {{ summary.calendarYear }}
                </p>
                <p v-if="summary.comment" class="text-sm font-medium">
                    {{ $t('examinations.course_comment') }}: {{ summary.comment }}
                </p>
            </div>

            <div
                v-for="row in subjects"
                :key="row.id"
                class="rounded-xl border border-border bg-card p-4 text-card-foreground shadow-sm print:border-black print:shadow-none"
            >
                <div class="flex items-start justify-between gap-3">
                    <div class="min-w-0 space-y-1">
                        <p class="text-base font-semibold leading-snug">
                            {{ row.subject || $t('examinations.subject') }}
                        </p>
                        <p v-if="row.subjectCode" class="font-mono text-xs text-muted-foreground">
                            {{ row.subjectCode }}
                        </p>
                    </div>
                    <div
                        class="shrink-0 rounded-lg bg-muted px-3 py-2 text-center print:bg-transparent print:border print:border-black"
                    >
                        <p class="text-[0.65rem] font-semibold uppercase tracking-wide text-muted-foreground">
                            {{ $t('examinations.grade') }}
                        </p>
                        <p class="text-xl font-bold leading-none">{{ row.grade || '—' }}</p>
                    </div>
                </div>

                <div class="mt-3 grid grid-cols-1 gap-2 border-t border-border pt-3 text-sm sm:grid-cols-2">
                    <div>
                        <p class="text-xs text-muted-foreground">{{ $t('examinations.session') }}</p>
                        <p>{{ row.session || '—' }}</p>
                    </div>
                    <div>
                        <p class="text-xs text-muted-foreground">{{ $t('examinations.course_comment') }}</p>
                        <p>{{ row.courseComment || '—' }}</p>
                    </div>
                </div>
            </div>

            <div
                v-if="subjects.length === 0"
                class="rounded-xl border border-dashed border-border p-6 text-center text-sm text-muted-foreground"
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
