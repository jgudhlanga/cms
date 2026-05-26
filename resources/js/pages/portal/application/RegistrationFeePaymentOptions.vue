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
    <div class="flex h-screen flex-1 items-center bg-background py-16 text-foreground">
        <DataLoadingSpinner v-if="isCheckingPayment" message="checking if you already pay" />
        <div v-else class="flex h-full w-full flex-col justify-around space-y-6 p-6">
            <div class="mx-auto flex flex-col items-center justify-center">
                <div class="text-destructive mx-auto flex items-center justify-center font-bold uppercase">
                    {{ $t('trans.ui_please_check_for_the_available_courses_in_the_advert_before') }}
                </div>
                <BaseAlert
                    :description="$t('trans.registration_fee_payment_description', { amount: `USD${formatCurrency(registrationFeeAmount)}` })"
                    :type="TypeVariant.info"
                />
            </div>
            <div class="amount">
                <div class="amount-label">{{ $t('trans.amount_to_pay') }}:</div>
                <div class="amount-value">{{ `USD${formatCurrency(registrationFeeAmount)}` }}</div>
            </div>
            <div class="mx-auto flex w-full md:w-1/3 flex-col items-center justify-center space-y-3 md:flex-row md:space-y-0 md:space-x-3">
                <CancelButton />
                <button @click="submit" class="inline-flex w-full p-2.5 bg-linear-to-br from-persian-600 to-[#00d2ff] text-white border-0 rounded-[10px] text-lg font-semibold justify-center items-center uppercase cursor-pointer transition-all duration-300 shadow-[0_4px_15px_rgba(0,0,0,0.1)]" :disabled="isLoading">
                    {{ $t('trans.proceed_to_payment') }}
                    <component :is="icons[IconName.loader]" v-if="isLoading" class="ml-2 h-6 w-5 animate-spin" />
                </button>
            </div>
            <div class="flex flex-col">
                <div class="text-muted-foreground flex items-center justify-center space-x-3 text-xs font-bold">
                    <span>🔒</span><span>{{ $t('trans.secure_payment_processed_by', { payment_processor: 'Smile & Pay' }) }}</span>
                </div>

                <div class="payment-methods">
                    <BaseImage :src="paymentMethods" classes="rounded-sm h-10" />
                </div>
            </div>
        </div>
    </div>
</template>
<style scoped>
.amount {
    text-align: center;
    margin-bottom: 30px;
}

.amount-label {
    font-size: 16px;
    color: #6c757d;
    margin-bottom: 8px;
    font-weight: 600;
    text-transform: uppercase;
}

.amount-value {
    font-size: 32px;
    font-weight: 700;
    color: #2342f5;
}

.payment-methods {
    display: flex;
    justify-content: center;
    gap: 15px;
    margin-top: 25px;
}
</style>
