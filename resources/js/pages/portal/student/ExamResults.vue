<script setup lang="ts">
import BaseAlert from '@/components/core/alert/BaseAlert.vue';
import { BaseButton } from '@/components/core/button';
import BaseInput from '@/components/core/form/text/BaseInput.vue';
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
import { Head, Link, useForm } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

type AccessPayload = {
    canViewResults: boolean;
    gate: 'clearance' | 'fees' | 'apprentice' | 'not_enrolled' | 'non_hexco';
    allowOnlineClearance: boolean;
    fees: {
        tuition: number;
        autoCardFee: number;
        partTimeLevy: number;
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
        pendingSections: string[];
        sections: Array<{ key: string; label: string; cleared: boolean }>;
    } | null;
    idValidation: { required: boolean; isValid: boolean; needsCorrection: boolean };
};

type SavedResult = {
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
    savedResults: { data: SavedResult[] } | SavedResult[];
    hasUnclaimedSession: boolean;
    lookupError: string | null;
    logBookFeeGapNotice: string | null;
    auth: AuthObject;
    errors: Record<string, string>;
}>();

const breadcrumbs: BreadcrumbItemInterface[] = [
    { title: props.auth.user.attributes?.name },
    { transKey: 'exam_results' },
];

const form = useForm({
    candidate_number: '',
});

const showLookupForm = ref(false);

const results = computed<SavedResult[]>(() => {
    const payload = props.savedResults as { data?: SavedResult[] } | SavedResult[];
    if (Array.isArray(payload)) {
        return payload;
    }
    return payload.data ?? [];
});

const hasSavedResults = computed(() => results.value.length > 0);
const canLookup = computed(() => props.access.canViewResults);
const canLookupAnother = computed(
    () => canLookup.value && hasSavedResults.value && props.hasUnclaimedSession,
);
const lookupExpanded = computed(
    () => (canLookup.value && !hasSavedResults.value) || (canLookupAnother.value && showLookupForm.value),
);

const money = formatFeeClearanceUsd;
const bankConversions = computed(() => props.access.fees?.bankConversions ?? []);

const tuitionPaymentRoute = computed(() => route('portal.profile.financials.pay', { returnTo: 'exam-results' }));
const canPayOutstanding = computed(
    () => props.access.gate === 'fees' && !!props.access.fees?.hasStudentNumber && Number(props.access.fees?.outstanding ?? 0) > 0,
);

const submitLookup = () => {
    form.post(route('portal.exam-results.lookup'), {
        preserveScroll: true,
    });
};
</script>

<template>
    <Head :title="$t('examinations.exam_results_title')" />
    <PageContainer :breadcrumbs="breadcrumbs">
        <div class="space-y-4">
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

            <div v-else-if="access.gate === 'fees' && access.fees" class="rounded-lg border border-border p-4 space-y-3">
                <h2 class="text-base font-semibold">{{ $t('trans.exam_results_fee_status') }}</h2>
                <BaseAlert
                    v-if="!access.fees.hasStudentNumber"
                    :type="TypeVariant.warning"
                    :description="$t('trans.exam_results_no_student_number')"
                />
                <ul class="space-y-1 text-sm">
                    <li
                        v-for="item in access.fees.breakdown"
                        :key="item.key"
                        class="flex justify-between gap-4"
                    >
                        <span>{{ item.label }}</span>
                        <span class="font-mono">{{ money(item.amount) }}</span>
                    </li>
                    <li class="flex justify-between gap-4 border-t border-border pt-2 font-medium">
                        <span>{{ $t('trans.exam_results_expected_total') }}</span>
                        <span class="font-mono">{{ money(access.fees.expectedTotal) }}</span>
                    </li>
                    <li class="space-y-1">
                        <div class="flex justify-between gap-4">
                            <span>{{ $t('trans.paid_from_bank') }}</span>
                            <span class="font-mono">{{ money(access.fees.paidFromBank ?? 0) }}</span>
                        </div>
                        <p
                            v-for="conversion in bankConversions"
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
                    <li class="flex justify-between gap-4">
                        <span>{{ $t('trans.exam_results_outstanding') }}</span>
                        <span class="font-mono">{{ money(access.fees.outstanding) }}</span>
                    </li>
                </ul>
                <BaseAlert
                    :type="access.fees.isFullyPaid ? TypeVariant.success : TypeVariant.warning"
                    :description="access.fees.isFullyPaid ? $t('trans.exam_results_fully_paid') : $t('trans.exam_results_not_fully_paid')"
                >
                    <div v-if="canPayOutstanding" class="mt-3 space-y-3">
                        <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                            <p class="text-xs text-muted-foreground">
                                {{ $t('trans.tuition_fee_payment_financials_hint') }}
                            </p>
                            <Link :href="tuitionPaymentRoute" class="inline-flex">
                                <BaseButton type="button">{{ $t('trans.pay_now') }}</BaseButton>
                            </Link>
                        </div>
                        <BaseAlert
                            :type="TypeVariant.info"
                            :title="$t('trans.tuition_fee_payment_disclaimer_title')"
                            :description="$t('trans.tuition_fee_accounts_disclaimer')"
                        />
                    </div>
                </BaseAlert>
                <p v-if="logBookFeeGapNotice" class="text-xs text-muted-foreground">{{ logBookFeeGapNotice }}</p>
            </div>

            <div v-else-if="access.gate === 'clearance' && access.clearance" class="rounded-lg border border-border p-4 space-y-3">
                <h2 class="text-base font-semibold">{{ $t('trans.exam_results_clearance_status') }}</h2>
                <BaseAlert
                    v-if="!access.canViewResults"
                    :type="TypeVariant.warning"
                    :description="$t('trans.exam_results_pending_offices')"
                />
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

            <template v-if="access.gate !== 'not_enrolled' && access.gate !== 'non_hexco'">
                <div v-if="hasSavedResults" class="space-y-3">
                    <div>
                        <h2 class="text-base font-semibold">{{ $t('examinations.exam_results_saved_list') }}</h2>
                        <p class="text-sm text-muted-foreground">{{ $t('examinations.exam_results_saved_list_hint') }}</p>
                    </div>

                    <Link
                        v-for="row in results"
                        :key="row.id"
                        :href="route('portal.exam-results.show', row.id)"
                        class="block rounded-xl border border-border bg-card p-4 text-card-foreground shadow-sm transition-colors active:bg-muted/40"
                    >
                        <div class="flex items-start justify-between gap-3">
                            <div class="min-w-0 space-y-1">
                                <p class="text-base font-semibold leading-tight">{{ row.session }}</p>
                                <p class="text-sm text-muted-foreground">
                                    {{ $t('examinations.calendar_year') }}: {{ row.calendarYear }}
                                </p>
                                <p class="font-mono text-sm">{{ row.candidateNumber }}</p>
                                <p v-if="row.comment || row.rawCourseComment" class="text-sm">
                                    <span class="text-muted-foreground">{{ $t('examinations.course_comment') }}:</span>
                                    {{ row.comment || row.rawCourseComment }}
                                </p>
                            </div>
                            <span class="shrink-0 text-sm font-medium text-primary">
                                {{ $t('examinations.exam_results_view') }}
                            </span>
                        </div>
                    </Link>
                </div>

                <BaseAlert
                    v-else
                    :type="TypeVariant.info"
                    :description="$t('examinations.exam_results_empty')"
                />
            </template>

            <div v-if="canLookupAnother && !showLookupForm" class="flex justify-start">
                <BaseButton type="button" @click="showLookupForm = true">
                    {{ $t('examinations.exam_results_lookup_another') }}
                </BaseButton>
            </div>

            <form
                v-if="lookupExpanded"
                class="rounded-lg border border-border p-4 space-y-3"
                @submit.prevent="submitLookup"
            >
                <p class="text-sm text-muted-foreground">
                    {{
                        hasSavedResults
                            ? $t('examinations.exam_results_lookup_another_hint')
                            : $t('examinations.exam_results_enter_candidate')
                    }}
                </p>
                <BaseInput
                    input-id="candidate_number"
                    :label="$t('examinations.candidate_number')"
                    v-model="form.candidate_number"
                    :disabled="!canLookup || form.processing"
                    :error="form.errors.candidate_number || errors.candidate_number"
                />
                <div class="flex flex-wrap gap-2">
                    <BaseButton type="submit" :disabled="!canLookup || form.processing" :processing="form.processing">
                        {{ $t('examinations.exam_results_lookup') }}
                    </BaseButton>
                    <BaseButton
                        v-if="canLookupAnother"
                        type="button"
                        @click="showLookupForm = false"
                    >
                        {{ $t('trans.cancel') }}
                    </BaseButton>
                </div>
            </form>

            <BaseAlert
                v-if="lookupError"
                :type="TypeVariant.warning"
                :description="lookupError"
            />
        </div>
    </PageContainer>
</template>
