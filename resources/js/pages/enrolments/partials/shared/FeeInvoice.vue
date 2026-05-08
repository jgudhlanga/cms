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
    <BaseCard :title="$t('finance.invoice')" color-variant="purple-500">
        <div class="flex flex-col gap-3">
            <template v-if="invoiceItems.length > 0">
                <div class="max-h-72 space-y-2 overflow-y-auto pr-1">
                    <div
                        v-for="invoiceItem in invoiceItems"
                        :key="invoiceItem.key"
                        class="rounded-md border border-gray-200 bg-white px-3 py-2 text-xs shadow-sm"
                    >
                        <div class="flex items-center justify-between gap-3">
                            <div class="text-accent-foreground font-medium">
                                {{ $t(invoiceItem.label) }}
                            </div>
                            <div class="text-accent-foreground font-semibold">
                                {{ formatMoney(invoiceItem.value as string | number) }}
                            </div>
                        </div>
                    </div>
                </div>
            </template>
            <template v-else>
                <Empty :message="$t('finance.no_invoice_items_found')" />
            </template>
        </div>
    </BaseCard>
</template>
