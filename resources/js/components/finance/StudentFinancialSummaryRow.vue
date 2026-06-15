<script setup lang="ts">
import FinancialSummaryCard from '@/components/finance/FinancialSummaryCard.vue';
import type { StudentLedgerSummary } from '@/types/finance';
import { CircleDollarSign, FileText, Receipt } from '@lucide/vue';
import { computed } from 'vue';

interface Props {
    summary: StudentLedgerSummary;
    formatUsd: (value: string | number) => string;
}

const props = defineProps<Props>();

const hasInvoicedAmount = computed(() => {
    const amount = Number(props.summary.totalInvoiced ?? 0);

    return Number.isFinite(amount) && amount > 0;
});
</script>

<template>
    <div class="grid grid-cols-1 gap-3 md:grid-cols-3">
        <FinancialSummaryCard
            :title-key="'finance.total_invoiced'"
            :hint-key="'finance.total_invoiced_hint'"
            :value="formatUsd(summary.totalInvoiced)"
            :icon="FileText"
            icon-class="bg-rose-500/15 text-rose-600 dark:text-rose-400"
            value-class="text-foreground"
        />
        <FinancialSummaryCard
            :title-key="'finance.total_payments'"
            :hint-key="'finance.total_payments_hint'"
            :value="formatUsd(summary.totalPayments)"
            :icon="Receipt"
            icon-class="bg-emerald-500/15 text-emerald-600 dark:text-emerald-400"
            value-class="text-foreground"
        />
        <FinancialSummaryCard
            :title-key="'finance.outstanding_balance'"
            :hint-key="'finance.outstanding_balance_hint'"
            :value="hasInvoicedAmount ? formatUsd(summary.outstandingBalance) : $t('finance.not_available')"
            :icon="CircleDollarSign"
            icon-class="bg-amber-500/15 text-amber-600 dark:text-amber-400"
            value-class="text-amber-600 dark:text-amber-400"
        />
    </div>
</template>
