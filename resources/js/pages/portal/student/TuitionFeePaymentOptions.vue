<script setup lang="ts">
import BaseAlert from '@/components/core/alert/BaseAlert.vue';
import BaseButton from '@/components/core/button/BaseButton.vue';
import BaseImage from '@/components/core/image/BaseImage.vue';
import PageContainer from '@/components/core/page/PageContainer.vue';
import { useDefaults } from '@/composables/core/useDefaults';
import { useUtils } from '@/composables/core/useUtils';
import { ButtonSize } from '@/enums/buttons';
import { ColorVariant } from '@/enums/colors';
import { TypeVariant } from '@/enums/type-variants';
import { errorAlert } from '@/lib/alerts';
import {
    formatFeeClearanceUsd,
    formatFeeClearanceZwgAmount,
    type FeeClearanceBankConversion,
} from '@/lib/feeClearanceMoney';
import type { AuthObject } from '@/types/data-pagination';
import type { BreadcrumbItemInterface } from '@/types/ui';
import axios from 'axios';
import { Head, Link } from '@inertiajs/vue3';
import { trans } from 'laravel-vue-i18n';
import { computed, ref } from 'vue';

type FeeSummary = {
    expectedTotal: number;
    paidFromBank?: number;
    paidFromLedger?: number;
    paidTotal: number;
    outstanding: number;
    bankConversions?: FeeClearanceBankConversion[];
};

interface Props {
    student: {
        id: number;
        name: string | null;
        studentNumber: string | null;
    };
    tuitionFee: {
        feeTypeId: number;
        ledgerableId: number;
        paymentAmount: string;
        currencyCode: string;
        itemName: string;
    };
    feeSummary: FeeSummary;
    returnTo: 'financials' | 'exam-results' | string;
    auth: AuthObject;
    errors: Record<string, string>;
}

const props = defineProps<Props>();
const isLoading = ref(false);

const { paymentMethods } = useDefaults();
const { generateRandomCode } = useUtils();

const backRoute = computed(() =>
    props.returnTo === 'exam-results' ? route('portal.exam-results') : route('portal.profile.financials'),
);

const breadcrumbs = computed<BreadcrumbItemInterface[]>(() => [
    { transChoiceKey: 'dashboard', href: route('portal.dashboard') },
    props.returnTo === 'exam-results'
        ? { transKey: 'exam_results', href: backRoute.value }
        : { transChoiceKey: 'students.financial', transChoiceKeyIndex: 2, href: backRoute.value },
    { transKey: 'tuition_fee_payment_title' },
]);

const paymentErrorMessage = (error: unknown): string => {
    const data = axios.isAxiosError(error) ? error.response?.data : null;
    if (data && typeof data === 'object') {
        const message = (data as { responseMessage?: string; message?: string }).responseMessage
            ?? (data as { message?: string }).message;
        if (typeof message === 'string' && message.trim() !== '') {
            return message;
        }

        const errors = (data as { errors?: Record<string, string[] | string> }).errors;
        if (errors && typeof errors === 'object') {
            const first = Object.values(errors).flat().find((value) => typeof value === 'string' && value.trim() !== '');
            if (typeof first === 'string') {
                return first;
            }
        }
    }

    return trans('trans.payment_error_description');
};

const submit = async () => {
    try {
        isLoading.value = true;
        const response = await axios.post(route('integrations.payments.initiate'), {
            orderReference: generateRandomCode('ORD'),
            feeTypeId: props.tuitionFee.feeTypeId,
            ledgerableId: props.tuitionFee.ledgerableId,
            amount: props.tuitionFee.paymentAmount,
            itemName: props.tuitionFee.itemName,
            itemDescription: props.tuitionFee.itemName,
            currencyCode: props.tuitionFee.currencyCode,
            firstName: props.auth.user.attributes.firstname ?? '',
            lastName: props.auth.user.attributes.lastname ?? '',
            email: props.auth.user.attributes.email ?? '',
        });

        if (response.data.paymentUrl) {
            window.location.href = response.data.paymentUrl;
        } else {
            errorAlert(response.data.responseMessage || trans('trans.payment_error_description'));
        }
    } catch (error: unknown) {
        errorAlert(paymentErrorMessage(error));
    } finally {
        isLoading.value = false;
    }
};

const usdAmount = computed(() => formatFeeClearanceUsd(props.tuitionFee.paymentAmount));
const money = formatFeeClearanceUsd;
const bankConversions = computed(() => props.feeSummary.bankConversions ?? []);
</script>

<template>
    <Head :title="$t('trans.tuition_fee_payment_title')" />
    <PageContainer :breadcrumbs="breadcrumbs" :back-url="backRoute">
        <div class="mx-auto w-full max-w-2xl space-y-4 px-4 pb-16 pt-4 sm:px-0">
            <div class="rounded-2xl border border-border bg-card p-6 shadow-sm sm:p-8">
                <div class="space-y-4">
                    <div>
                        <h1 class="text-xl font-semibold text-foreground">
                            {{ $t('trans.tuition_fee_payment_title') }}
                        </h1>
                        <p class="mt-2 text-sm text-muted-foreground">
                            {{ $t('trans.tuition_fee_payment_subtitle') }}
                        </p>
                    </div>

                    <div class="rounded-xl border border-border bg-muted/20 p-4 text-sm">
                        <dl class="grid gap-3 sm:grid-cols-2">
                            <div>
                                <dt class="text-muted-foreground">{{ $tChoice('trans.student', 1) }}</dt>
                                <dd class="font-medium text-foreground">{{ student.name || '—' }}</dd>
                            </div>
                            <div>
                                <dt class="text-muted-foreground">{{ $tChoice('trans.student_number', 1) }}</dt>
                                <dd class="font-medium text-foreground">{{ student.studentNumber || '—' }}</dd>
                            </div>
                            <div>
                                <dt class="text-muted-foreground">{{ $t('trans.paid_from_bank') }}</dt>
                                <dd class="font-medium text-foreground">{{ money(feeSummary.paidFromBank ?? 0) }}</dd>
                                <p
                                    v-for="conversion in bankConversions"
                                    :key="`${conversion.rate}-${conversion.date}`"
                                    class="mt-1 text-xs text-muted-foreground"
                                >
                                    {{ $t('trans.fee_clearance_zwg_conversion_note', {
                                        amount: formatFeeClearanceZwgAmount(conversion.originalAmount),
                                        label: conversion.label,
                                        date: conversion.date,
                                    }) }}
                                </p>
                            </div>
                            <div>
                                <dt class="text-muted-foreground">{{ $t('trans.paid_online') }}</dt>
                                <dd class="font-medium text-foreground">{{ money(feeSummary.paidFromLedger ?? 0) }}</dd>
                            </div>
                        </dl>
                    </div>

                    <BaseAlert
                        :type="TypeVariant.info"
                        :title="$t('trans.tuition_fee_payment_disclaimer_title')"
                        :description="$t('trans.tuition_fee_accounts_disclaimer')"
                    />

                    <div class="rounded-xl border border-border bg-muted/30 px-5 py-4">
                        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                            <div class="space-y-1">
                                <div class="text-xs font-semibold uppercase tracking-wide text-muted-foreground">
                                    {{ $t('trans.amount_to_pay') }}
                                </div>
                                <div class="text-2xl font-semibold text-foreground sm:text-3xl">
                                    {{ usdAmount }}
                                </div>
                                <p class="text-xs text-muted-foreground">
                                    {{ $t('trans.tuition_fee_payment_outstanding_hint', { total: money(feeSummary.expectedTotal) }) }}
                                </p>
                            </div>
                            <div class="space-y-1 text-xs font-medium text-muted-foreground sm:text-right">
                                <p>{{ $t('trans.ui_secure_redirect_to_payment_gateway') }}</p>
                                <p>{{ $t('trans.tuition_fee_payment_next_step') }}</p>
                            </div>
                        </div>
                    </div>

                    <div class="grid gap-2 text-sm text-muted-foreground">
                        <div class="flex justify-between gap-4">
                            <span>{{ $t('trans.exam_results_paid_total') }}</span>
                            <span class="font-medium text-foreground">{{ money(feeSummary.paidTotal) }}</span>
                        </div>
                        <div class="flex justify-between gap-4">
                            <span>{{ $t('trans.exam_results_outstanding') }}</span>
                            <span class="font-medium text-foreground">{{ money(feeSummary.outstanding) }}</span>
                        </div>
                    </div>
                </div>

                <div class="mt-6 flex flex-col gap-3 sm:grid sm:grid-cols-2">
                    <Link :href="backRoute" class="order-2 w-full sm:order-1">
                        <BaseButton
                            type="button"
                            :size="ButtonSize.lg"
                            :variant="ColorVariant.primary_outline"
                            class="w-full"
                        >
                            {{ $t('trans.back') }}
                        </BaseButton>
                    </Link>
                    <BaseButton
                        type="button"
                        :size="ButtonSize.lg"
                        :variant="ColorVariant.primary"
                        class="order-1 w-full sm:order-2"
                        :processing="isLoading"
                        @click="submit"
                    >
                        {{ isLoading ? $t('trans.ui_redirecting_to_payment') : $t('trans.proceed_to_payment') }}
                    </BaseButton>
                </div>

                <div class="mt-8 space-y-4">
                    <div class="flex items-center justify-center gap-2 text-xs font-semibold text-muted-foreground">
                        <span>{{ $t('trans.secure_payment_processed_by', { payment_processor: 'Smile & Pay' }) }}</span>
                    </div>

                    <div class="flex items-center justify-center">
                        <BaseImage :src="paymentMethods" classes="h-10 rounded-sm opacity-90" />
                    </div>
                </div>
            </div>
        </div>
    </PageContainer>
</template>
