<script setup lang="ts">
import { BaseButton } from '@/components/core/button';
import BaseIcon from '@/components/core/icon/BaseIcon.vue';
import AnimatedCheckMark from '@/components/core/util/AnimatedCheckMark.vue';
import { useUtils } from '@/composables/core/useUtils';
import { ColorVariant } from '@/enums/colors';
import { errorAlert, getModalEdit, successAlert } from '@/lib/alerts';
import { APP_MODULE_KEYS } from '@/lib/constants';
import { IconName } from '@/lib/icons';
import { cn } from '@/lib/utils';
import HttpService from '@/services/http.service';
import { useModalStore } from '@/store/core/useModalStore';
import { usePaymentDebugStore } from '@/store/integrations/usePaymentDebugStore';
import { PaymentCheckResponse } from '@/types/tools';
import { trans } from 'laravel-vue-i18n';
import { storeToRefs } from 'pinia';
import { computed, ref, watch } from 'vue';

defineProps<{
    canUpdate: boolean;
}>();

const checkData = ref<PaymentCheckResponse>();
const { reload } = storeToRefs(usePaymentDebugStore());
const { modals, isOpen, closeModal } = useModalStore();
const { formatCurrency, formatDate } = useUtils();
const processingUpdate = ref(false);

const destroyModal = () => {
    closeModal(APP_MODULE_KEYS.show_payment_status);
};

watch(modals!, () => {
    checkData.value = getModalEdit(APP_MODULE_KEYS.show_payment_status);
});

const composeDetails = computed(() => {
    return {
        attributes: {
            amount: checkData?.value?.amount,
            clientFee: checkData?.value?.clientFee,
            createdDate: checkData?.value?.createdDate,
            currency: checkData?.value?.currency,
            itemName: checkData?.value?.itemName,
            merchantFee: checkData?.value?.merchantFee,
            merchantId: checkData?.value?.merchantId,
            orderReference: checkData?.value?.orderReference,
            paymentReference: checkData?.value?.reference,
            paymentStatus: checkData?.value?.status,
            paymentOption: checkData?.value?.paymentOption,
            resultUrl: checkData?.value?.resultUrl,
            returnUrl: checkData?.value?.returnUrl,
        },
    };
});

const statusTone = computed(() => {
    const status = String(composeDetails.value.attributes.paymentStatus ?? '').toLowerCase();

    if (status === 'paid') {
        return 'paid';
    }

    if (status === 'failed' || status === 'cancelled' || status === 'canceled' || status === 'error') {
        return 'failed';
    }

    return 'pending';
});

const headerClass = computed(() => {
    if (statusTone.value === 'paid') {
        return 'from-green-400 to-green-600';
    }

    if (statusTone.value === 'failed') {
        return 'from-red-400 to-red-600';
    }

    return 'from-amber-400 to-amber-600';
});

const updateLedgers = async () => {
    processingUpdate.value = true;

    try {
        await HttpService.post(route('integrations.payments-debug.update'), composeDetails.value.attributes);
        successAlert(trans('integrations.payments_debug_updated'));
        destroyModal();
        reload.value = true;
    } catch (error: unknown) {
        const axiosError = error as { response?: { data?: { message?: string } } };
        errorAlert(axiosError?.response?.data?.message ?? trans('integrations.payments_debug_update_error'));
    } finally {
        processingUpdate.value = false;
    }
};
</script>

<template>
    <Transition name="fade">
        <div v-if="isOpen(APP_MODULE_KEYS.show_payment_status)" class="fixed inset-0 z-20 flex items-center justify-center">
            <div class="absolute inset-0 z-0 bg-black opacity-50"></div>
            <div class="bg-background relative z-10 w-[768px] overflow-x-hidden overflow-y-auto rounded-2xl shadow-lg outline-hidden">
                <div class="flex flex-1 items-center bg-transparent">
                    <div class="w-full overflow-hidden rounded-xl bg-white shadow-lg transition-all duration-300 hover:shadow-xl">
                        <div :class="cn('flex flex-col items-center bg-gradient-to-br px-6 py-8', headerClass)">
                            <AnimatedCheckMark v-if="statusTone === 'paid'" />
                            <BaseIcon v-else :name="IconName.info" size="48" class="text-white" />
                            <h1 class="mt-2 text-2xl font-bold text-white">{{ composeDetails?.attributes?.paymentStatus }}</h1>
                            <p class="mt-2 text-center text-white/90">{{ $t('trans.ui_transaction_found') }}</p>
                        </div>
                        <div class="px-6 py-6">
                            <div class="mb-6 rounded-xl border border-gray-100 bg-gray-50 p-5" v-if="composeDetails">
                                <h2 class="mb-3 flex items-center text-lg font-semibold text-gray-700">
                                    <BaseIcon :name="IconName.receipt" size="18" class="mr-2 text-green-600" />
                                    {{ $t('trans.transaction_details') }}
                                </h2>
                                <div class="space-y-3">
                                    <div class="flex justify-between">
                                        <span class="text-gray-600">{{ $t('trans.reference') }}</span>
                                        <span class="font-mono text-gray-800">#{{ composeDetails?.attributes?.paymentReference ?? '—' }}</span>
                                    </div>
                                    <div class="flex justify-between">
                                        <span class="text-gray-600">{{ $tChoice('trans.amount', 1) }}</span>
                                        <span class="font-semibold text-green-600">{{
                                            formatCurrency(String(composeDetails?.attributes?.amount ?? ''))
                                        }}</span>
                                    </div>
                                    <div class="flex justify-between">
                                        <span class="text-gray-600">{{ $tChoice('trans.payment_option', 1) }}</span>
                                        <span class="text-gray-800">{{ composeDetails?.attributes?.paymentOption ?? '—' }}</span>
                                    </div>
                                    <div class="flex justify-between">
                                        <span class="text-gray-600">{{ $t('trans.date') }}</span>
                                        <span class="text-gray-800">{{ formatDate(composeDetails?.attributes?.createdDate!) }}</span>
                                    </div>
                                    <div class="flex justify-between border-t border-gray-200 pt-2">
                                        <div class="text-gray-600">{{ $tChoice('trans.status', 1) }}</div>
                                        <div
                                            :class="
                                                cn(
                                                    'flex items-center rounded-full px-2 py-1 font-medium uppercase',
                                                    statusTone === 'paid' && 'bg-green-100 text-green-600',
                                                    statusTone === 'failed' && 'bg-red-100 text-red-600',
                                                    statusTone === 'pending' && 'bg-amber-100 text-amber-700',
                                                )
                                            "
                                        >
                                            {{ composeDetails?.attributes?.paymentStatus }}
                                        </div>
                                    </div>
                                    <div class="flex items-center justify-center space-x-3">
                                        <BaseButton
                                            type="button"
                                            classes="rounded-full"
                                            :variant="ColorVariant.warning_outline"
                                            :title="$t('trans.close')"
                                            @click="destroyModal"
                                        />
                                        <BaseButton
                                            v-if="canUpdate"
                                            type="button"
                                            :processing="processingUpdate"
                                            classes="rounded-full"
                                            :title="$t('integrations.payments_debug_update')"
                                            :variant="ColorVariant.success"
                                            @click="updateLedgers"
                                        />
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </Transition>
</template>
