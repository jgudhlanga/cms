<script setup lang="ts">
import PortalApplicationShell from '@/components/portal/PortalApplicationShell.vue';
import BaseAlert from '@/components/core/alert/BaseAlert.vue';
import { BaseButton } from '@/components/core/button';
import BaseImage from '@/components/core/image/BaseImage.vue';
import DataLoadingSpinner from '@/components/core/loader/DataLoadingSpinner.vue';
import { useDefaults } from '@/composables/core/useDefaults';
import { useUtils } from '@/composables/core/useUtils';
import { ColorVariant } from '@/enums/colors';
import { IconName } from '@/enums/icons';
import { TypeVariant } from '@/enums/type-variants';
import { errorAlert } from '@/lib/alerts';
import { icons } from '@/lib/icons';
import { cn } from '@/lib/utils';
import CancelButton from '@/pages/portal/application/partials/CancelButton.vue';
import HttpService from '@/services/http.service';
import { AuthObject } from '@/types/data-pagination';
import { FeeStructure } from '@/types/institution';
import { Head } from '@inertiajs/vue3';
import axios from 'axios';
import { trans } from 'laravel-vue-i18n';
import { computed, onMounted, ref } from 'vue';

interface Props {
    registrationFee: FeeStructure;
    applicationFeeId: number;
    applicationFeeStatus?: string;
    applicationFeeStatusLabel: string;
    levelName?: string | null;
    intakeName?: string | null;
    applicationStep?: 'level' | 'fee' | 'apply';
    auth: AuthObject;
    errors: object;
}

const props = withDefaults(defineProps<Props>(), {
    applicationStep: 'fee',
    applicationFeeStatus: 'awaiting-payment',
});

const { user } = props.auth;
const checkData = ref<{ status: string } | null>(null);
const isCheckingPayment = ref(false);

const { paymentMethods } = useDefaults();
const { generateRandomCode, formatCurrency } = useUtils();
const registrationFeeAmount = props.registrationFee?.attributes?.localFcaAmount ?? '20.00';

const statusBadgeClass = computed(() => {
    switch (props.applicationFeeStatus) {
        case 'paid':
            return 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-300';
        case 'awaiting-payment':
            return 'bg-amber-100 text-amber-800 dark:bg-amber-900/30 dark:text-amber-300';
        default:
            return 'bg-muted text-muted-foreground';
    }
});

const isLoading = ref(false);

const formData = {
    orderReference: generateRandomCode('ORD'),
    feeTypeId: props.registrationFee?.attributes?.feeTypeId ?? '',
    ledgerableId: props.applicationFeeId,
    amount: registrationFeeAmount,
    itemName: props.registrationFee?.attributes?.feeType ?? '',
    itemDescription: props.registrationFee?.attributes?.feeType ?? '',
    currencyCode: '840',
    firstName: user.attributes.firstname ?? '',
    lastName: user.attributes.lastname ?? '',
    email: user.attributes.email ?? '',
};

const checkPaymentStatus = async () => {
    isCheckingPayment.value = true;
    try {
        checkData.value = await HttpService.post(route('check-payment-status-for-current-user'), {
            feeTypeId: props.registrationFee?.attributes?.feeTypeId ?? '',
            ledgerableId: props.applicationFeeId,
        });
    } catch (error: any) {
        checkData.value = null;
        errorAlert('Failed to check payment status. ' + error);
    } finally {
        isCheckingPayment.value = false;
    }
};

const submit = async () => {
    try {
        isLoading.value = true;
        const response = await axios.post(route('integrations.payments.initiate'), formData);
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

onMounted(async () => {
    await checkPaymentStatus();
    const studentId = user.attributes?.studentId;
    if (String(checkData?.value?.status)?.toLowerCase() === 'paid') {
        if (Number(studentId) > 0) {
            window.location.href = route('portal.add-program', { student: studentId });
            return;
        }
        window.location.href = route('portal.application.create');
    }
});
</script>
<template>
    <Head :title="$t('trans.portal_registration_step_application_fee')" />
    <PortalApplicationShell :intake-name="intakeName">
        <div class="min-h-svh px-4 pb-12 sm:px-6">
            <DataLoadingSpinner v-if="isCheckingPayment" :message="$t('trans.ui_checking_payment_status')" />

            <div v-else class="mx-auto w-full max-w-2xl">
                <div class="mb-6 text-center">
                    <h1 class="text-xl font-semibold text-foreground">
                        {{ $t('trans.portal_registration_step_application_fee') }}
                    </h1>
                    <p class="mt-2 text-sm text-muted-foreground">
                        {{ $t('trans.portal_application_fee_payment_subtitle') }}
                    </p>
                </div>

                <div class="rounded-2xl border border-border bg-card p-6 shadow-sm sm:p-8">
                    <div class="space-y-4">
                        <div class="rounded-xl border border-border bg-muted/20 p-4 text-sm">
                            <dl class="grid gap-2 sm:grid-cols-2">
                                <div v-if="levelName">
                                    <dt class="text-muted-foreground">{{ $tChoice('trans.level', 1) }}</dt>
                                    <dd class="font-medium text-foreground">{{ levelName }}</dd>
                                </div>
                                <div>
                                    <dt class="text-muted-foreground">{{ $tChoice('trans.status', 1) }}</dt>
                                    <dd>
                                        <span
                                            :class="
                                                cn(
                                                    'inline-flex rounded-full px-2.5 py-0.5 text-xs font-semibold',
                                                    statusBadgeClass,
                                                )
                                            "
                                        >
                                            {{ applicationFeeStatusLabel }}
                                        </span>
                                    </dd>
                                </div>
                            </dl>
                        </div>

                        <BaseAlert
                            :title="$t('trans.portal_application_fee_required_title')"
                            :description="
                                $t('trans.registration_fee_payment_description', {
                                    amount: `USD${formatCurrency(registrationFeeAmount)}`,
                                })
                            "
                            :type="TypeVariant.info"
                        />

                        <div class="rounded-xl border border-border bg-muted/30 px-5 py-4">
                            <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                                <div class="space-y-0.5">
                                    <div class="text-xs font-semibold tracking-wide text-muted-foreground uppercase">
                                        {{ $t('trans.amount_to_pay') }}
                                    </div>
                                    <div class="text-2xl font-semibold text-foreground sm:text-3xl">
                                        {{ `USD${formatCurrency(registrationFeeAmount)}` }}
                                    </div>
                                </div>
                                <div class="space-y-1 text-xs font-medium text-muted-foreground sm:text-right">
                                    <p>{{ $t('trans.ui_secure_redirect_to_payment_gateway') }}</p>
                                    <p>{{ $t('trans.portal_application_fee_payment_next_step') }}</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="mt-6 flex flex-col gap-3 sm:grid sm:grid-cols-2">
                        <BaseButton
                            type="button"
                            class="order-1 w-full normal-case sm:order-2"
                            :variant="ColorVariant.primary"
                            :processing="isLoading"
                            @click="submit"
                        >
                            {{ isLoading ? $t('trans.ui_redirecting_to_payment') : $t('trans.proceed_to_payment') }}
                        </BaseButton>
                        <div class="order-2 sm:order-1">
                            <CancelButton />
                        </div>
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
    </PortalApplicationShell>
</template>
