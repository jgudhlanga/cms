<script setup lang="ts">
import { BarChart3 } from '@lucide/vue';
import type { StudentLedgerSummary } from '@/types/finance';
import { computed } from 'vue';
import { trans } from 'laravel-vue-i18n';

interface Props {
    summary: StudentLedgerSummary;
    formatUsd: (value: string | number) => string;
}

const props = defineProps<Props>();

const totalInvoiced = computed(() => Number(props.summary.totalInvoiced ?? 0));
const hasInvoicedAmount = computed(
    () => Number.isFinite(totalInvoiced.value) && totalInvoiced.value > 0,
);

const paidPercent = computed(() => Math.min(100, Math.max(0, props.summary.paidPercent ?? 0)));

const settledLabel = computed(() =>
    hasInvoicedAmount.value
        ? trans('finance.settled_of', {
              paid: props.formatUsd(props.summary.totalPayments),
              invoiced: props.formatUsd(props.summary.totalInvoiced),
          })
        : trans('finance.not_available'),
);
</script>

<template>
    <div v-if="hasInvoicedAmount" class="rounded-xl border border-border bg-card p-3 shadow-sm">
        <div class="mb-3 flex items-center gap-2 text-xs font-semibold uppercase tracking-wide text-foreground">
            <BarChart3 class="h-4 w-4 text-primary" />
            <span>{{ $t('finance.totals_vs_invoiced') }}</span>
        </div>
        <div>
            <div class="mb-2 flex items-center justify-between text-xs">
                <span class="text-muted-foreground">{{ $t('finance.paid_percent') }}</span>
                <span class="font-bold text-foreground">{{ paidPercent }}%</span>
            </div>
            <div class="h-2.5 w-full overflow-hidden rounded-full bg-muted">
                <div
                    class="h-full rounded-full bg-primary transition-all"
                    :style="{ width: `${paidPercent}%` }"
                />
            </div>
            <p class="mt-2 text-[11px] text-muted-foreground">
                {{ settledLabel }}
            </p>
            </div>
    </div>
</template>
