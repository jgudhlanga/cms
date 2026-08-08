<script setup lang="ts">
import BaseButton from '@/components/core/button/BaseButton.vue';
import StudentPaymentEntryBadge from '@/components/finance/StudentPaymentEntryBadge.vue';
import StudentPaymentSourceBadge from '@/components/finance/StudentPaymentSourceBadge.vue';
import StudentPaymentLedgerMobileList from '@/components/finance/StudentPaymentLedgerMobileList.vue';
import Empty from '@/components/core/util/Empty.vue';
import DataLoadingSpinner from '@/components/core/loader/DataLoadingSpinner.vue';
import { ButtonSize } from '@/enums/buttons';
import { ColorVariant } from '@/enums/colors';
import type { ParsedStudentPaymentReceipt } from '@/types/finance';
import { ClipboardList, Printer } from '@lucide/vue';

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

const emit = defineEmits<{
    print: [];
}>();
</script>

<template>
    <div class="rounded-xl border border-border bg-card shadow-sm">
        <div
            class="flex flex-wrap items-center justify-between gap-2 border-b border-border px-3 py-3 sm:px-4"
        >
            <div class="flex min-w-0 flex-col gap-1">
                <div class="flex flex-wrap items-center gap-2">
                    <ClipboardList class="h-4 w-4 text-muted-foreground" />
                    <h3 class="text-sm font-semibold text-foreground">
                        {{ $t('finance.transaction_statement') }}
                    </h3>
                    <span
                        class="rounded-md bg-primary/10 px-2 py-0.5 text-[10px] font-medium text-primary"
                    >
                        {{ $t('finance.cr_db_format') }}
                    </span>
                </div>
                <p class="text-[11px] text-muted-foreground">
                    {{ $t('finance.transaction_statement_sources_hint') }}
                </p>
            </div>
            <BaseButton
                type="button"
                :size="ButtonSize.sm"
                :variant="ColorVariant.danger"
                class="shrink-0"
                @click="emit('print')"
            >
                <Printer class="h-3.5 w-3.5" aria-hidden="true" />
                {{ $t('finance.print_statement') }}
            </BaseButton>
        </div>

        <div class="p-2">
            <DataLoadingSpinner v-if="isLoading" />
            <template v-else>
                <template v-if="receipts.length > 0">
                    <div class="md:hidden">
                        <StudentPaymentLedgerMobileList
                            :receipts="receipts"
                            :format-ledger-date="formatLedgerDate"
                            :sanitize-receipt-description="sanitizeReceiptDescription"
                            :format-ledger-usd-amount="formatLedgerUsdAmount"
                            :format-running-balance="formatRunningBalance"
                            :is-charge-entry="isChargeEntry"
                        />
                    </div>
                    <div class="hidden overflow-x-auto md:block">
                        <table class="j-table">
                            <thead class="j-thead">
                                <tr>
                                    <th class="j-th text-left">{{ $t('finance.transaction_date') }}</th>
                                    <th class="j-th text-left">{{ $t('finance.description') }}</th>
                                    <th class="j-th text-right">{{ $t('finance.debit_column') }}</th>
                                    <th class="j-th text-right">{{ $t('finance.credit_column') }}</th>
                                    <th class="j-th text-right">{{ $t('finance.running_balance') }}</th>
                                </tr>
                            </thead>
                            <tbody class="j-tbody">
                                <tr v-for="receipt in receipts" :key="receipt.id" class="j-tr">
                                    <td class="j-td whitespace-nowrap">
                                        {{ formatLedgerDate(receipt.attributes.transactionDate) }}
                                    </td>
                                    <td class="j-td max-w-md whitespace-normal">
                                        <div class="flex flex-wrap items-center gap-2">
                                            <StudentPaymentEntryBadge :is-charge="isChargeEntry(receipt)" />
                                            <StudentPaymentSourceBadge :source="receipt.attributes.source" />
                                            <span class="wrap-break-word text-foreground">
                                                {{ sanitizeReceiptDescription(receipt) }}
                                            </span>
                                        </div>
                                    </td>
                                    <td
                                        class="j-td text-right font-medium"
                                        :class="
                                            isChargeEntry(receipt)
                                                ? 'text-amber-600! dark:text-amber-400!'
                                                : 'text-muted-foreground'
                                        "
                                    >
                                        {{
                                            isChargeEntry(receipt)
                                                ? formatLedgerUsdAmount(receipt.debit)
                                                : $t('finance.empty_amount')
                                        }}
                                    </td>
                                    <td
                                        class="j-td text-right font-medium"
                                        :class="
                                            !isChargeEntry(receipt)
                                                ? 'text-emerald-600! dark:text-emerald-400!'
                                                : 'text-muted-foreground'
                                        "
                                    >
                                        {{
                                            !isChargeEntry(receipt)
                                                ? formatLedgerUsdAmount(receipt.credit)
                                                : $t('finance.empty_amount')
                                        }}
                                    </td>
                                    <td class="j-td text-right font-bold text-foreground">
                                        {{ formatRunningBalance(receipt) }}
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </template>
                <Empty v-else :message="$t('finance.no_transactions_found')" class="py-8" />
            </template>
        </div>
    </div>
</template>
