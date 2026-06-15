<script setup lang="ts">
import StudentPaymentEntryBadge from '@/components/finance/StudentPaymentEntryBadge.vue';
import Empty from '@/components/core/util/Empty.vue';
import DataLoadingSpinner from '@/components/core/loader/DataLoadingSpinner.vue';
import type { ParsedStudentPaymentReceipt } from '@/types/finance';

interface Props {
    receipts: ParsedStudentPaymentReceipt[];
    isLoading?: boolean;
    formatLedgerDate: (value?: string | null) => string;
    sanitizeReceiptDescription: (receipt: ParsedStudentPaymentReceipt) => string;
    formatLedgerUsdAmount: (amount: number) => string;
    formatRunningBalance: (receipt: ParsedStudentPaymentReceipt) => string;
    isChargeEntry: (receipt: ParsedStudentPaymentReceipt) => boolean;
}

withDefaults(defineProps<Props>(), {
    isLoading: false,
});
</script>

<template>
    <DataLoadingSpinner v-if="isLoading" />
    <template v-else>
        <template v-if="receipts.length > 0">
            <div class="space-y-2">
                <div
                    v-for="receipt in receipts"
                    :key="receipt.id"
                    class="rounded-md border border-border bg-card px-3 py-2.5 text-xs shadow-sm"
                >
                    <div class="flex items-center justify-between gap-2">
                        <span class="font-medium text-foreground">
                            {{ formatLedgerDate(receipt.attributes.transactionDate) }}
                        </span>
                        <StudentPaymentEntryBadge :is-charge="isChargeEntry(receipt)" />
                    </div>
                    <p class="mt-1.5 wrap-break-word leading-snug text-muted-foreground">
                        {{ sanitizeReceiptDescription(receipt) }}
                    </p>
                    <div class="mt-2 grid grid-cols-3 gap-2 border-t border-border pt-2 text-[11px]">
                        <div>
                            <span class="text-muted-foreground">{{ $t('finance.debit_column') }}</span>
                            <p
                                class="mt-0.5 font-medium"
                                :class="
                                    isChargeEntry(receipt)
                                        ? 'text-amber-600 dark:text-amber-400'
                                        : 'text-muted-foreground'
                                "
                            >
                                {{
                                    isChargeEntry(receipt)
                                        ? formatLedgerUsdAmount(receipt.debit)
                                        : $t('finance.empty_amount')
                                }}
                            </p>
                        </div>
                        <div>
                            <span class="text-muted-foreground">{{ $t('finance.credit_column') }}</span>
                            <p
                                class="mt-0.5 font-medium"
                                :class="
                                    !isChargeEntry(receipt)
                                        ? 'text-emerald-600 dark:text-emerald-400'
                                        : 'text-muted-foreground'
                                "
                            >
                                {{
                                    !isChargeEntry(receipt)
                                        ? formatLedgerUsdAmount(receipt.credit)
                                        : $t('finance.empty_amount')
                                }}
                            </p>
                        </div>
                        <div class="text-right">
                            <span class="text-muted-foreground">{{ $t('finance.running_balance') }}</span>
                            <p class="mt-0.5 font-bold text-foreground">
                                {{ formatRunningBalance(receipt) }}
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </template>
        <Empty v-else :message="$t('finance.no_transactions_found')" class="py-8" />
    </template>
</template>
