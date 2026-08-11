<script setup lang="ts">
import BaseAlert from '@/components/core/alert/BaseAlert.vue';
import { BaseButton } from '@/components/core/button';
import BaseInput from '@/components/core/form/text/BaseInput.vue';
import { TypeVariant } from '@/enums/type-variants';
import {
    formatFeeClearanceUsd,
    formatFeeClearanceZwgAmount,
    type FeeClearanceBankConversion,
} from '@/lib/feeClearanceMoney';
import { hasAbility } from '@/lib/permissions';
import HttpService from '@/services/http.service';
import type { Student } from '@/types/students';
import { trans } from 'laravel-vue-i18n';
import { computed, onMounted, ref } from 'vue';

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
}>();

const loading = ref(false);
const lookingUp = ref(false);
const loadingStatement = ref(false);
const errorMessage = ref<string | null>(null);
const lookupError = ref<string | null>(null);
const access = ref<AccessPayload | null>(null);
const savedResults = ref<SavedResult[]>([]);
const hasUnclaimedSession = ref(false);
const logBookFeeGapNotice = ref('');
const candidateNumber = ref('');
const showLookupForm = ref(false);
const activeSummary = ref<Summary | null>(null);
const activeSubjects = ref<SubjectRow[]>([]);
const statementAllowed = ref(false);

const canViewTab = () => hasAbility('viewStudentExamResults:students');

const hasSavedResults = computed(() => savedResults.value.length > 0);
const canLookup = computed(() => access.value?.canViewResults === true);
const canLookupAnother = computed(
    () => canLookup.value && hasSavedResults.value && hasUnclaimedSession.value,
);
const lookupExpanded = computed(
    () => (canLookup.value && !hasSavedResults.value) || (canLookupAnother.value && showLookupForm.value),
);

const money = formatFeeClearanceUsd;
const bankConversions = computed(() => access.value?.fees?.bankConversions ?? []);

const resolveLoadError = (error: any, fallback: string): string => {
    if (error?.response?.status === 403) {
        return trans('examinations.exam_results_permission_denied');
    }

    return error?.response?.data?.message ?? fallback;
};

const applyIndexPayload = (data: {
    access: AccessPayload;
    savedResults: SavedResult[];
    hasUnclaimedSession: boolean;
    logBookFeeGapNotice?: string;
}) => {
    access.value = data.access;
    savedResults.value = data.savedResults ?? [];
    hasUnclaimedSession.value = Boolean(data.hasUnclaimedSession);
    if (data.logBookFeeGapNotice) {
        logBookFeeGapNotice.value = data.logBookFeeGapNotice;
    }
};

const fetchIndex = async () => {
    if (!props.student?.id) {
        return;
    }

    loading.value = true;
    errorMessage.value = null;

    try {
        const response = await HttpService.get(route('v1.students.exam-results.index', props.student.id));
        applyIndexPayload(response.data);
    } catch (error: any) {
        errorMessage.value = resolveLoadError(error, 'Unable to load exam results.');
    } finally {
        loading.value = false;
    }
};

const openStatement = async (resultId: number) => {
    if (!props.student?.id) {
        return;
    }

    loadingStatement.value = true;
    errorMessage.value = null;

    try {
        const response = await HttpService.get(
            route('v1.students.exam-results.show', [props.student.id, resultId]),
        );
        access.value = response.data.access;
        statementAllowed.value = Boolean(response.data.allowed);
        activeSummary.value = response.data.summary;
        activeSubjects.value = response.data.subjects ?? [];
        if (response.data.logBookFeeGapNotice) {
            logBookFeeGapNotice.value = response.data.logBookFeeGapNotice;
        }
    } catch (error: any) {
        errorMessage.value = resolveLoadError(error, 'Unable to load exam statement.');
    } finally {
        loadingStatement.value = false;
    }
};

const backToList = () => {
    activeSummary.value = null;
    activeSubjects.value = [];
    statementAllowed.value = false;
};

const submitLookup = async () => {
    if (!props.student?.id || !canLookup.value) {
        return;
    }

    lookingUp.value = true;
    lookupError.value = null;
    errorMessage.value = null;

    try {
        const response = await HttpService.post(
            route('v1.students.exam-results.lookup', props.student.id),
            { candidate_number: candidateNumber.value },
        );

        applyIndexPayload({
            access: response.data.access,
            savedResults: response.data.savedResults ?? [],
            hasUnclaimedSession: response.data.hasUnclaimedSession,
        });

        if (response.data.found && response.data.summary) {
            statementAllowed.value = Boolean(response.data.allowed);
            activeSummary.value = response.data.summary;
            activeSubjects.value = response.data.subjects ?? [];
            showLookupForm.value = false;
            candidateNumber.value = '';
        }
    } catch (error: any) {
        const message =
            error?.response?.status === 403
                ? trans('examinations.exam_results_permission_denied')
                : error?.response?.data?.errors?.candidate_number?.[0]
                    ?? error?.response?.data?.message
                    ?? 'Unable to look up exam results.';

        if (error?.response?.status === 422 && error?.response?.data?.data) {
            applyIndexPayload({
                access: error.response.data.data.access ?? access.value,
                savedResults: error.response.data.data.savedResults ?? savedResults.value,
                hasUnclaimedSession: error.response.data.data.hasUnclaimedSession ?? hasUnclaimedSession.value,
            });
        }

        lookupError.value = message;
    } finally {
        lookingUp.value = false;
    }
};

const printStatement = () => {
    window.print();
};

onMounted(fetchIndex);
</script>

<template>
    <div v-if="!canViewTab()" class="p-4">
        <BaseAlert :type="TypeVariant.danger" :description="$t('trans.forbidden_message')" />
    </div>
    <div v-else class="space-y-4 p-2">
        <BaseAlert v-if="errorMessage" :type="TypeVariant.danger" :description="errorMessage" />
        <p v-if="loading || loadingStatement" class="text-sm text-muted-foreground">Loading…</p>

        <template v-if="activeSummary">
            <div class="print:hidden space-y-4">
                <template v-if="!statementAllowed">
                    <BaseAlert
                        :type="TypeVariant.warning"
                        :description="$t('trans.exam_results_access_denied')"
                    />
                    <BaseButton type="button" @click="backToList">
                        {{ $t('examinations.exam_results_back_to_list') }}
                    </BaseButton>
                </template>

                <div v-else class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                    <div class="min-w-0">
                        <h2 class="text-lg font-semibold leading-tight">{{ activeSummary.session }}</h2>
                        <p class="mt-1 text-sm text-muted-foreground break-words">
                            {{ activeSummary.candidateNumber }} · {{ activeSummary.calendarYear }}
                            <span v-if="activeSummary.comment"> · {{ activeSummary.comment }}</span>
                        </p>
                    </div>
                    <div class="flex flex-wrap gap-2">
                        <BaseButton type="button" @click="printStatement">
                            {{ $t('examinations.exam_results_print') }}
                        </BaseButton>
                        <BaseButton type="button" @click="backToList">
                            {{ $t('examinations.exam_results_back_to_list') }}
                        </BaseButton>
                    </div>
                </div>
            </div>

            <div v-if="statementAllowed" class="exam-results-statement space-y-3">
                <p class="text-xs font-medium text-red-600 print:text-red-600">
                    {{ $t('examinations.exam_results_unofficial_use') }}
                </p>

                <div
                    v-for="row in activeSubjects"
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
            </div>
        </template>

        <template v-else-if="access">
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
                />
                <p class="text-xs text-muted-foreground">{{ logBookFeeGapNotice }}</p>
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

                    <button
                        v-for="row in savedResults"
                        :key="row.id"
                        type="button"
                        class="block w-full rounded-xl border border-border bg-card p-4 text-left text-card-foreground shadow-sm transition-colors hover:bg-muted/40"
                        @click="openStatement(row.id)"
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
                    </button>
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
                    input-id="admin_candidate_number"
                    :label="$t('examinations.candidate_number')"
                    v-model="candidateNumber"
                    :disabled="!canLookup || lookingUp"
                    :error="lookupError ?? undefined"
                />
                <div class="flex flex-wrap gap-2">
                    <BaseButton type="submit" :disabled="!canLookup || lookingUp" :processing="lookingUp">
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
                v-if="lookupError && !lookupExpanded"
                :type="TypeVariant.warning"
                :description="lookupError"
            />
        </template>
    </div>
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
