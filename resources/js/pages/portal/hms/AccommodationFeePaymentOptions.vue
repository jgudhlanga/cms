<script setup lang="ts">
import BaseImage from '@/components/core/image/BaseImage.vue';
import DataLoadingSpinner from '@/components/core/loader/DataLoadingSpinner.vue';
import StudentPageHeader from '@/components/shared/students/StudentPageHeader.vue';
import { useDefaults } from '@/composables/core/useDefaults';
import { useUtils } from '@/composables/core/useUtils';
import { IconName } from '@/enums/icons';
import { errorAlert } from '@/lib/alerts';
import { icons } from '@/lib/icons';
import { AuthObject } from '@/types/data-pagination';
import { FeeStructure } from '@/types/institution';
import type { StudentAccommodationFeesResponse } from '@/types/hms';
import axios from 'axios';
import { trans } from 'laravel-vue-i18n';
import { computed, ref } from 'vue';
import { router } from '@inertiajs/vue3';
import type { BreadcrumbItemInterface } from '@/types/ui';

interface Props {
    accommodationFee: FeeStructure | null;
    fees: StudentAccommodationFeesResponse;
    auth: AuthObject;
    errors: object;
}

const props = defineProps<Props>();
const { user } = props.auth;
const { paymentMethods } = useDefaults();
const { generateRandomCode, formatCurrency } = useUtils();

const isLoading = ref(false);

const amountDue = computed(() => {
    const due = Number(props.fees?.due ?? 0);

    if (due > 0) {
        return props.fees?.due ?? '0.00';
    }

    return props.accommodationFee?.attributes?.localFcaAmount ?? '0.00';
});
const feeTypeId = computed(() => props.accommodationFee?.attributes?.feeTypeId ?? null);

const buildFormData = () => ({
    orderReference: generateRandomCode('ORD'),
    feeTypeId: feeTypeId.value,
    amount: amountDue.value,
    itemName: props.accommodationFee?.attributes?.feeType ?? 'Student Accommodation Fee',
    itemDescription: props.accommodationFee?.attributes?.feeType ?? 'Student Accommodation Fee',
    currencyCode: '840',
    firstName: user.attributes.firstname ?? '',
    lastName: user.attributes.lastname ?? '',
    email: user.attributes.email ?? '',
});

const goBack = () => {
    router.visit(route('portal.profile.accommodations'));
};

const submit = async () => {
    if (Number(amountDue.value) <= 0 || !feeTypeId.value) {
        return;
    }

    try {
        isLoading.value = true;
        const response = await axios.post(route('integrations.payments.initiate'), buildFormData());

        if (response.data.paymentUrl) {
            window.location.href = response.data.paymentUrl;
        } else {
            errorAlert(response.data.responseMessage);
        }
    } catch {
        errorAlert(trans('trans.payment_error_description'));
    } finally {
        isLoading.value = false;
    }
};
const breadcrumbs = computed<BreadcrumbItemInterface[]>(() => [
    { transChoiceKey: 'dashboard', href: route('portal.dashboard') },
    {transChoiceKey: trans('students.accommodation'), href: route('portal.profile.accommodations') },
    {transKey: 'students.hms_payment'},
]);
</script>

<template>
    <Head :title="$t('students.hms_payment')" />
    <PageContainer :breadcrumbs="breadcrumbs">
    <div class="min-h-svh bg-background px-4 pb-12 pt-28 text-foreground sm:px-6">
        <div class="mx-auto w-full max-w-2xl">
            <div class="rounded-2xl border border-border bg-card p-6 shadow-sm sm:p-8">
                <div class="space-y-4">
                    <p class="text-center text-sm text-muted-foreground">
                        {{ $t('students.accommodation_payment_required') }}
                    </p>
                    <div class="rounded-xl border border-border bg-muted/30 px-5 py-4">
                        <div class="flex items-center justify-between gap-4">
                            <div class="space-y-0.5">
                                <div class="text-xs font-semibold tracking-wide text-muted-foreground uppercase">
                                    {{ $t('trans.amount_to_pay') }}
                                </div>
                                <div class="text-2xl font-semibold text-foreground sm:text-3xl">
                                    {{ `USD${formatCurrency(amountDue)}` }}
                                </div>
                            </div>
                            <div class="text-muted-foreground hidden text-right text-xs font-medium sm:block">
                                {{ $t('trans.ui_secure_redirect_to_payment_gateway') }}
                            </div>
                        </div>
                    </div>
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
                        :disabled="isLoading || Number(amountDue) <= 0"
                        @click="submit"
                    >
                        <span>{{ $t('trans.proceed_to_payment') }}</span>
                        <component :is="icons[IconName.loader]" v-if="isLoading" class="ml-2 h-5 w-5 animate-spin" />
                    </button>
                </div>

                <div class="mt-8 space-y-4">
                    <div class="text-muted-foreground flex items-center justify-center gap-2 text-xs font-semibold">
                        <component :is="icons[IconName.shield]" class="h-4 w-4" />
                        <span>{{ $t('trans.secure_payment_processed_by', { payment_processor: 'Smile & Pay' }) }}</span>
                    </div>

                    <div class="flex items-center justify-center">
                        <BaseImage :src="paymentMethods" classes="h-10 rounded-sm opacity-90" />
                    </div>
                </div>
            </div>
        </div>
    </div>
    </PageContainer>
</template>
