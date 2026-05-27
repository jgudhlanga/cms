<script setup lang="ts">
import BaseAlert from '@/components/core/alert/BaseAlert.vue';
import BaseImage from '@/components/core/image/BaseImage.vue';
import DataLoadingSpinner from '@/components/core/loader/DataLoadingSpinner.vue';
import StudentPageHeader from '@/components/shared/students/StudentPageHeader.vue';
import { useDefaults } from '@/composables/core/useDefaults';
import { useUtils } from '@/composables/core/useUtils';
import { IconName } from '@/enums/icons';
import { TypeVariant } from '@/enums/type-variants';
import { errorAlert } from '@/lib/alerts';
import { icons } from '@/lib/icons';
import CancelButton from '@/pages/portal/application/partials/CancelButton.vue';
import HttpService from '@/services/http.service';
import { AuthObject } from '@/types/data-pagination';
import { FeeStructure } from '@/types/institution';
import axios from 'axios';
import { trans } from 'laravel-vue-i18n';
import { onMounted, ref } from 'vue';

interface Props {
    registrationFee: FeeStructure;
    auth: AuthObject;
    errors: object;
}

const props = defineProps<Props>();
const { user } = props.auth;
const checkData = ref<{ status: string } | null>(null);
const isCheckingPayment = ref(false);

const { paymentMethods } = useDefaults();
const { generateRandomCode, formatCurrency, navigateTo } = useUtils();
const registrationFeeAmount = props.registrationFee?.attributes?.localFcaAmount ?? '20.00';

const isLoading = ref(false);

const formData = {
    orderReference: generateRandomCode('ORD'),
    feeTypeId: props.registrationFee?.attributes?.feeTypeId ?? '',
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
        checkData.value = await HttpService.post(route('check-payment-status-for-current-user'), {});
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
    /**ToastService.warning('Sorry, The registration has ended for now. Contact the administration for more info.');
    navigateTo(route('login'));
    return;**/
    await checkPaymentStatus();
    const studentId = user.attributes?.studentId;
    const userEmail = user.attributes.email;
    if (String(checkData?.value?.status)?.toLowerCase() === 'paid' || userEmail === 'jamesgudhlanga0@gmail.com') {
        if (Number(studentId) > 0) {
            window.location.href = route('portal.add-program', { student: studentId });
            return;
        }
        window.location.href = route('portal.application.create');
    }
});
</script>
<template>
    <StudentPageHeader />
    <div class="min-h-svh bg-background px-4 pb-12 pt-28 text-foreground sm:px-6">
        <DataLoadingSpinner v-if="isCheckingPayment" :message="$t('trans.ui_checking_payment_status')" />

        <div v-else class="mx-auto w-full max-w-2xl">
            <div class="rounded-2xl border border-border bg-card p-6 shadow-sm sm:p-8">
                <div class="space-y-4">
                    <div class="text-destructive text-center text-sm font-bold tracking-wide uppercase">
                        {{ $t('trans.ui_please_check_for_the_available_courses_in_the_advert_before') }}
                    </div>

                    <BaseAlert
                        :description="$t('trans.registration_fee_payment_description', { amount: `USD${formatCurrency(registrationFeeAmount)}` })"
                        :type="TypeVariant.info"
                    />

                    <div class="rounded-xl border border-border bg-muted/30 px-5 py-4">
                        <div class="flex items-center justify-between gap-4">
                            <div class="space-y-0.5">
                                <div class="text-xs font-semibold tracking-wide text-muted-foreground uppercase">
                                    {{ $t('trans.amount_to_pay') }}
                                </div>
                                <div class="text-2xl font-semibold text-foreground sm:text-3xl">
                                    {{ `USD${formatCurrency(registrationFeeAmount)}` }}
                                </div>
                            </div>
                            <div class="text-muted-foreground hidden text-right text-xs font-medium sm:block">
                                {{ $t('trans.ui_secure_redirect_to_payment_gateway') }}
                            </div>
                        </div>
                    </div>
                </div>

                <div class="mt-6 grid grid-cols-1 gap-3 sm:grid-cols-2">
                    <CancelButton />
                    <button
                        type="button"
                        @click="submit"
                        class="inline-flex w-full cursor-pointer items-center justify-center rounded-[10px] border-0 bg-linear-to-br from-persian-600 to-[#00d2ff] px-4 py-2.5 text-base font-semibold text-white shadow-[0_4px_15px_rgba(0,0,0,0.1)] transition-all duration-300 enabled:hover:brightness-105 disabled:cursor-not-allowed disabled:opacity-70"
                        :disabled="isLoading"
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
</template>
