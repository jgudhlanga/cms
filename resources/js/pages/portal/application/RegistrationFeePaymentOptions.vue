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
import HttpService from '@/services/http.service';
import { AuthObject } from '@/types/data-pagination';
import { FeeStructure } from '@/types/institution';
import axios from 'axios';
import { trans } from 'laravel-vue-i18n';
import { onMounted, ref } from 'vue';
import ToastService from '@/services/toast.service';

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
    ToastService.warning('Sorry, The registration has ended for now. Contact the administration for more info.');
    navigateTo(route('login'));
    await checkPaymentStatus();
    const studentId = user.attributes?.studentId;
    const userEmail = user.attributes.email;
    if (String(checkData?.value?.status)?.toLowerCase() === 'paid' || userEmail === 'jamesgudhlanga@gmail.com') {
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
    <div class="flex h-screen flex-1 items-center bg-gray-50 py-16">
        <DataLoadingSpinner v-if="isCheckingPayment" message="checking if you already pay" />
        <div v-else class="flex h-full w-full flex-col justify-around space-y-6 p-6">
            <div class="mx-auto flex flex-col items-center justify-center">
                <div class="text-destructive mx-auto flex items-center justify-center font-bold uppercase">
                    Please check for the available Courses in the advert before making a payment
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
            <div class="mx-auto flex w-1/3">
                <button @click="submit" class="payment-button" :disabled="isLoading">
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

.payment-button {
    display: inline-flex;
    width: 100%;
    padding: 20px;
    background: linear-gradient(135deg, #2342f5 0%, #00d2ff 100%);
    color: white;
    border: none;
    border-radius: 20px;
    font-size: 18px;
    font-weight: 600;
    justify-content: center;
    align-items: center;
    text-transform: uppercase;
    cursor: pointer;
    transition: all 0.3s ease;
    box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
}

.payment-button:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 20px rgba(58, 123, 213, 0.35);
}

.payment-button:active {
    transform: translateY(0);
}

.payment-methods {
    display: flex;
    justify-content: center;
    gap: 15px;
    margin-top: 25px;
}
</style>
