<script setup lang="ts">
import { BaseButton } from '@/components/core/button';
import { useUtils } from '@/composables/core/useUtils';
import { ButtonSize } from '@/enums/buttons';
import { ColorVariant } from '@/enums/colors';
import { errorAlert, openModal } from '@/lib/alerts';
import { APP_MODULE_KEYS } from '@/lib/constants';
import { cn } from '@/lib/utils';
import HttpService from '@/services/http.service';
import { Ledger } from '@/types/integrations';
import { PaymentCheckResponse } from '@/types/tools';
import { trans } from 'laravel-vue-i18n';
import { ref } from 'vue';

defineProps<{
    ledgers: Ledger[];
}>();

const { formatDate, formatCurrency } = useUtils();
const checkingReference = ref<string | null>(null);

const statusChipClass = (status: string | null | undefined): string => {
    const value = String(status ?? '').toLowerCase();

    if (value === 'paid') {
        return 'border-emerald-500/30 bg-emerald-500/10 text-emerald-700 dark:text-emerald-400';
    }

    if (value === 'failed' || value === 'cancelled' || value === 'canceled') {
        return 'border-destructive/30 bg-destructive/10 text-destructive';
    }

    return 'border-amber-500/40 bg-amber-500/10 text-amber-700 dark:text-amber-400';
};

const checkStatus = async (orderReference: string) => {
    checkingReference.value = orderReference;

    try {
        const response = await HttpService.post(route('integrations.payments-debug.check', orderReference), {});
        const message = response?.message;

        if (message) {
            errorAlert(`${message} (${orderReference})`);
        } else {
            openModal({ name: APP_MODULE_KEYS.show_payment_status, edit: response as PaymentCheckResponse });
        }
    } catch (error: unknown) {
        const axiosError = error as { response?: { data?: { message?: string } } };
        errorAlert(axiosError?.response?.data?.message ?? trans('integrations.payments_debug_check_error'));
    } finally {
        checkingReference.value = null;
    }
};
</script>

<template>
    <table class="j-table">
        <thead class="j-thead">
            <tr class="j-th">
                <th class="j-th text-left">{{ $t('trans.ui_date_created') }}</th>
                <th class="j-th text-left">{{ $tChoice('trans.fee_type', 1) }}</th>
                <th class="j-th text-left">{{ $tChoice('trans.amount', 1) }}</th>
                <th class="j-th text-left">{{ $t('trans.ui_order_reference') }}</th>
                <th class="j-th text-left">{{ $t('trans.ui_payment_reference') }}</th>
                <th class="j-th text-left">{{ $t('trans.ui_payment_status') }}</th>
                <th class="j-th text-center">{{ $tChoice('trans.action', 2) }}</th>
            </tr>
        </thead>
        <tbody class="j-tbody">
            <tr class="j-tr" v-for="ledger in ledgers" :key="ledger.id">
                <td class="j-td">
                    {{ formatDate(String(ledger.attributes.createdAt), 'LL') }}
                    <span v-if="ledger.attributes.deletedAt" class="text-destructive ml-1 text-[11px] font-semibold uppercase">
                        {{ $t('integrations.payments_debug_deleted') }}
                    </span>
                </td>
                <td class="j-td">
                    {{ ledger.attributes.feeType ?? '—' }}
                </td>
                <td class="j-td tabular-nums">
                    {{ formatCurrency(String(ledger.attributes.amount ?? '')) }}
                    <span v-if="ledger.attributes.currency" class="text-muted-foreground ml-1 text-xs">{{ ledger.attributes.currency }}</span>
                </td>
                <td class="j-td font-mono text-xs">
                    {{ ledger.attributes.systemReference ?? '—' }}
                </td>
                <td class="j-td font-mono text-xs">
                    {{ ledger.attributes.paymentReference ?? '—' }}
                </td>
                <td class="j-td">
                    <span
                        :class="
                            cn(
                                'inline-flex items-center rounded-full border px-2 py-0.5 text-[11px] font-semibold uppercase',
                                statusChipClass(ledger.attributes.paymentStatus),
                            )
                        "
                    >
                        {{ ledger.attributes.paymentStatus ?? '—' }}
                    </span>
                </td>
                <td class="j-td text-center">
                    <BaseButton
                        :disabled="checkingReference !== null"
                        :processing="checkingReference === ledger.attributes.systemReference"
                        type="button"
                        :size="ButtonSize.xs"
                        classes="rounded-full"
                        :variant="ColorVariant.success"
                        :title="$t('integrations.payments_debug_check_status')"
                        @click="checkStatus(String(ledger.attributes.systemReference))"
                    />
                </td>
            </tr>
        </tbody>
    </table>
</template>
