<script setup lang="ts">
import type { ParsedStudentPaymentReceipt } from '@/types/finance';
import { computed } from 'vue';

interface Props {
    receipt: ParsedStudentPaymentReceipt;
    formatMoney: (value: string | number | null | undefined, currencyCode?: string) => string;
    originalAmountLine: string | null;
    isUsd: boolean;
}

const props = defineProps<Props>();

const displayAmount = computed(() =>
    props.formatMoney(
        props.receipt.credit > 0 ? props.receipt.credit : props.receipt.debit,
        props.receipt.attributes.isoCurrencyCode,
    ),
);

const amountClass = computed(() =>
    props.isUsd
        ? 'bg-emerald-500/15 text-emerald-700 dark:text-emerald-400'
        : 'bg-muted text-muted-foreground',
);
</script>

<template>
    <div class="flex flex-col items-end gap-1">
        <span :class="amountClass" class="rounded-full px-2 py-0.5 text-[10px] font-semibold">
            {{ displayAmount }}
        </span>
        <span v-if="originalAmountLine" class="text-[10px] leading-tight text-muted-foreground">
            {{ originalAmountLine }}
        </span>
    </div>
</template>
