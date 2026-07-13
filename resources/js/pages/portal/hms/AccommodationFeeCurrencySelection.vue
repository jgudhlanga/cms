<script setup lang="ts">
import BaseRadioGroup from '@/components/core/form/radio-group/BaseRadioGroup.vue';
import { useUtils } from '@/composables/core/useUtils';
import { AuthObject } from '@/types/data-pagination';
import { FeeStructure } from '@/types/institution';
import type { StudentAccommodationFeesResponse } from '@/types/hms';
import type { RadioGroupOption } from '@/types/forms';
import type { BreadcrumbItemInterface } from '@/types/ui';
import { router } from '@inertiajs/vue3';
import { trans } from 'laravel-vue-i18n';
import { computed, ref } from 'vue';

interface ExchangeRate {
    rate: string;
    date: string;
    label: string;
}

interface PaymentQuote {
    usdDue: string;
    zwgDue: string | null;
    exchangeRate: ExchangeRate | null;
}

interface Props {
    accommodationFee: FeeStructure | null;
    fees: StudentAccommodationFeesResponse;
    hostelApplicationId: number;
    quote: PaymentQuote;
    auth: AuthObject;
    errors: object;
}

const props = defineProps<Props>();
const { formatCurrency } = useUtils();

const selectedCurrency = ref(props.quote.zwgDue !== null ? 'usd' : 'usd');

const currencyOptions = computed<RadioGroupOption[]>(() => {
    const options: RadioGroupOption[] = [
        {
            inputId: 'accommodation-currency-usd',
            value: 'usd',
            label: `${trans('students.accommodation_payment_currency_usd')} — USD ${formatCurrency(props.quote.usdDue)}`,
        },
    ];

    if (props.quote.zwgDue !== null) {
        options.push({
            inputId: 'accommodation-currency-zwg',
            value: 'zwg',
            label: `${trans('students.accommodation_payment_currency_zwg')} — ZWG ${formatCurrency(props.quote.zwgDue)}`,
        });
    }

    return options;
});

const zwgUnavailable = computed(() => props.quote.zwgDue === null);

const breadcrumbs = computed<BreadcrumbItemInterface[]>(() => [
    { transChoiceKey: 'dashboard', href: route('portal.dashboard') },
    { transChoiceKey: trans('students.accommodation'), href: route('portal.profile.accommodations') },
    { transKey: 'students.accommodation_payment_currency_title' },
]);

const goBack = () => {
    router.visit(route('portal.profile.accommodations'));
};

const continueToPayment = () => {
    if (selectedCurrency.value === 'zwg' && zwgUnavailable.value) {
        return;
    }

    router.visit(route('portal.profile.accommodations.pay', { currency: selectedCurrency.value }));
};
</script>

<template>
    <Head :title="$t('students.accommodation_payment_currency_title')" />
    <PageContainer :breadcrumbs="breadcrumbs">
        <div class="min-h-svh bg-background px-4 pb-12 pt-28 text-foreground sm:px-6">
            <div class="mx-auto w-full max-w-2xl">
                <div class="rounded-2xl border border-border bg-card p-6 shadow-sm sm:p-8">
                    <div class="space-y-4">
                        <p class="text-center text-sm text-muted-foreground">
                            {{ $t('students.accommodation_payment_currency_description') }}
                        </p>

                        <BaseRadioGroup
                            v-model="selectedCurrency"
                            :options="currencyOptions"
                            :label="$t('students.accommodation_payment_currency_label')"
                            orientation="vertical"
                        />

                        <p
                            v-if="quote.exchangeRate"
                            class="text-xs text-muted-foreground"
                        >
                            {{ $t('students.accommodation_payment_exchange_rate_note', {
                                label: quote.exchangeRate.label,
                                date: quote.exchangeRate.date,
                            }) }}
                        </p>

                        <p
                            v-if="zwgUnavailable"
                            class="text-xs text-amber-600 dark:text-amber-400"
                        >
                            {{ $t('students.accommodation_payment_zwg_unavailable') }}
                        </p>
                    </div>

                    <div class="mt-6 grid grid-cols-1 gap-3 sm:grid-cols-2">
                        <button
                            type="button"
                            class="inline-flex w-full cursor-pointer items-center justify-center rounded-[10px] border border-border bg-card px-4 py-2.5 text-base font-semibold text-foreground transition-colors hover:bg-muted/50"
                            @click="goBack"
                        >
                            {{ $t('trans.cancel') }}
                        </button>
                        <button
                            type="button"
                            class="inline-flex w-full cursor-pointer items-center justify-center rounded-[10px] border-0 bg-linear-to-br from-persian-600 to-[#00d2ff] px-4 py-2.5 text-base font-semibold text-white shadow-[0_4px_15px_rgba(0,0,0,0.1)] transition-all duration-300 enabled:hover:brightness-105 disabled:cursor-not-allowed disabled:opacity-70"
                            :disabled="selectedCurrency === 'zwg' && zwgUnavailable"
                            @click="continueToPayment"
                        >
                            {{ $t('trans.continue') }}
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </PageContainer>
</template>
