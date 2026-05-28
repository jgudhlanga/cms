<script setup lang="ts">
import StudentPaymentAmountCell from '@/components/finance/StudentPaymentAmountCell.vue';
import StudentPaymentTypeBadge from '@/components/finance/StudentPaymentTypeBadge.vue';
import Empty from '@/components/core/util/Empty.vue';
import DataLoadingSpinner from '@/components/core/loader/DataLoadingSpinner.vue';
import type { ParsedStudentPaymentReceipt } from '@/types/finance';

interface Props {
    receipts: ParsedStudentPaymentReceipt[];
    isLoading?: boolean;
    formatReceiptDate: (value?: string | null) => string;
    sanitizeReceiptDescription: (receipt: ParsedStudentPaymentReceipt) => string;
    receiptReference: (receipt: ParsedStudentPaymentReceipt) => string;
    formatMoney: (value: string | number | null | undefined, currencyCode?: string) => string;
    originalAmountNearReference: (receipt: ParsedStudentPaymentReceipt) => string | null;
    isUsdAmount: (receipt: ParsedStudentPaymentReceipt) => boolean;
}

withDefaults(defineProps<Props>(), {
    isLoading: false,
});
</script>

<template>
    <DataLoadingSpinner v-if="isLoading" />
    <template v-else>
        <template v-if="receipts.length > 0">
            <div class="max-h-72 space-y-2 overflow-y-auto pr-1">
                <div
                    v-for="receipt in receipts"
                    :key="receipt.id"
                    class="rounded-md border border-border bg-card px-3 py-2 text-xs shadow-sm"
                >
                    <div class="flex items-center justify-between gap-3">
                        <div class="font-medium text-accent-foreground">
                            {{ formatReceiptDate(receipt.attributes.transactionDate) }}
                        </div>
                        <StudentPaymentTypeBadge :credit="receipt.credit" />
                    </div>
                    <div class="mt-1 whitespace-normal wrap-break-word text-[10px] leading-tight text-muted-foreground">
                        {{ sanitizeReceiptDescription(receipt) }}
                    </div>
                    <div class="mt-2 text-[11px]">
                        <div class="text-muted-foreground">
                            {{ $t('finance.reference') }}:
                            {{ receiptReference(receipt) }}
                        </div>
                        <div class="mt-1 flex justify-end">
                            <StudentPaymentAmountCell
                                :receipt="receipt"
                                :format-money="formatMoney"
                                :original-amount-line="originalAmountNearReference(receipt)"
                                :is-usd="isUsdAmount(receipt)"
                            />
                        </div>
                    </div>
                </div>
            </div>
        </template>
        <Empty v-else :message="$t('finance.no_receipts_found')" />
    </template>
</template>
