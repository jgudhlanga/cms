<script setup lang="ts">
import { BaseButton } from '@/components/core/button';
import { useUtils } from '@/composables/core/useUtils';
import { ButtonSize } from '@/enums/buttons';
import { ColorVariant } from '@/enums/colors';
import { errorAlert, openModal } from '@/lib/alerts';
import { APP_MODULE_KEYS } from '@/lib/constants';
import HttpService from '@/services/http.service';
import { Ledger } from '@/types/integrations';
import { PaymentCheckResponse } from '@/types/tools';
import { ref } from 'vue';

interface Props {
    ledgers: Ledger[];
}

defineProps<Props>();
const { formatDate } = useUtils();
const isChecking = ref(false);
const checkData = ref<PaymentCheckResponse | null>(null);
const checkStatus = async (orderReference: string) => {
    isChecking.value = true;
    try {
        const response = await HttpService.post(route('integrations.payments.check-status', orderReference), {});
        const message = response?.message;
        if (message) {
            errorAlert(`${message} (${orderReference})`);
        } else {
            checkData.value = response;
            openModal({ name: APP_MODULE_KEYS.show_payment_status, edit: checkData.value });
        }
    } catch (error: any) {
        const message = error?.response?.data?.message;
        if (message) {
            errorAlert(error?.response?.data?.message);
        } else {
            errorAlert('An error occurred while checking payment status. Try again later.');
        }
    } finally {
        isChecking.value = false;
    }
};

const disableWhenIsPaid = (status: string) => {
    return status.toLowerCase() === 'paid';
};
</script>

<template>
    <table class="j-table">
        <thead class="j-thead">
            <tr class="j-th">
                <th class="j-th text-left">#</th>
                <th class="j-th text-left">{{ $t('trans.ui_date_created') }}</th>
                <th class="j-th text-left">{{ $t('trans.ui_order_reference') }}</th>
                <th class="j-th text-left">{{ $t('trans.ui_payment_reference') }}</th>
                <th class="j-th text-left">{{ $t('trans.ui_payment_status') }}</th>
                <th class="j-th text-center">{{ $tChoice('trans.action', 2) }}</th>
            </tr>
        </thead>
        <tbody class="j-tbody">
            <tr class="j-tr" v-for="ledger in ledgers" :key="ledger.id">
                <td class="j-td">
                    {{ ledger.id }}
                </td>
                <td class="j-td">
                    {{ formatDate(String(ledger.attributes.createdAt), 'LL') }}
                </td>
                <td class="j-td">
                    {{ ledger.attributes.systemReference ?? '---' }}
                </td>
                <td class="j-td">
                    {{ ledger.attributes.paymentReference ?? '---' }}
                </td>
                <td class="j-td">
                    {{ ledger.attributes.paymentStatus ?? '---' }}
                </td>
                <td class="j-td flex items-center justify-center space-x-2 text-center">
                    <BaseButton
                        :disabled="disableWhenIsPaid(String(ledger.attributes.paymentStatus)) || isChecking"
                        type="button"
                        :size="ButtonSize.xs"
                        classes="rounded-full"
                        :variant="ColorVariant.success"
                        @click="checkStatus(String(ledger.attributes.systemReference))"
                        :title="$t('trans.ui_check_status')"
                    />
                </td>
            </tr>
        </tbody>
    </table>
</template>
