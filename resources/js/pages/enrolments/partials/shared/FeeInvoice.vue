<script setup lang="ts">
import { useUtils } from '@/composables/core/useUtils';
import { computed } from 'vue';

interface Props {
    tuition?: string | number;
    autoCardFee?: string | number;
    partTimeLevy?: string | number;
}
const props = defineProps<Props>();

const { formatCurrency } = useUtils();

const formatMoney = (value: string | number): string => `USD ${formatCurrency(String(value))}`;

const invoiceItems = computed(() =>
    [
        {
            key: 'tuition',
            label: 'finance.tuition',
            value: props.tuition,
        },
        {
            key: 'auto-card-fee',
            label: 'finance.autocard_fee',
            value: props.autoCardFee,
        },
        {
            key: 'part-time-levy',
            label: 'finance.part_time_levy',
            value: props.partTimeLevy,
        },
    ].filter((item) => Boolean(item.value)),
);
</script>

<template>
    <div class="flex flex-col gap-2">
        <h3 class="text-[0.65rem] font-semibold tracking-[0.12em] text-muted-foreground uppercase">
            {{ $t('finance.invoice') }}
        </h3>
        <div v-if="invoiceItems.length > 0" class="flex flex-col gap-1.5">
            <div
                v-for="invoiceItem in invoiceItems"
                :key="invoiceItem.key"
                class="rounded-lg border border-border bg-card px-3 py-2 text-xs"
            >
                <div class="flex items-center justify-between gap-3">
                    <span class="font-medium text-foreground">
                        {{ $t(invoiceItem.label) }}
                    </span>
                    <span class="font-semibold text-foreground">
                        {{ formatMoney(invoiceItem.value as string | number) }}
                    </span>
                </div>
            </div>
        </div>
        <p v-else class="text-xs text-muted-foreground">
            {{ $t('finance.no_invoice_items_found') }}
        </p>
    </div>
</template>
