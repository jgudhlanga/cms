<script setup lang="ts">
import { useUtils } from '@/composables/core/useUtils';
import { useStudentsFinancials } from '@/composables/finance/useStudentsFinancials';
import { computed, onMounted } from 'vue';
import moment from 'moment';

interface Props {
    studentId: string;
}
const props = defineProps<Props>();

const { formatCurrency } = useUtils();
const { getStudentFinancialsByStudentNumber, isLoading, studentPaymentReceipts } = useStudentsFinancials();

const toAmount = (value?: string | number | null): number => {
    if (value === null || value === undefined || value === '') {
        return 0;
    }

    const parsed = Number(value);
    return Number.isFinite(parsed) ? parsed : 0;
};

const formatMoney = (value: string | number | null | undefined, currencyCode?: string): string => {
    const amount = toAmount(value);
    const code = currencyCode || 'USD';
    return `${code} ${formatCurrency(String(amount))}`;
};

const formatReceiptDate = (value?: string | null): string => {
    if (!value) {
        return '---';
    }

    const parsedDate = moment(value, ['DD-MM-YY HH:mm:ss', 'DD-MM-YYYY HH:mm:ss', moment.ISO_8601], true);
    return parsedDate.isValid() ? parsedDate.format('ll') : '---';
};
const parsedReceipts = computed(() =>
    studentPaymentReceipts.value.map((receipt) => ({
        ...receipt,
        credit: toAmount(receipt.attributes.amountCredit),
        debit: toAmount(receipt.attributes.amountDebit),
    })),
);

onMounted(async () => {
    await getStudentFinancialsByStudentNumber(props.studentId);
});

</script>

<template>
    <BaseCard title="Receipt" description="What the student has paid" color-variant="green-500">
        <div class="flex flex-col gap-3">
            <DataLoadingSpinner v-if="isLoading" />
            <template v-else>
                <template v-if="studentPaymentReceipts && studentPaymentReceipts.length > 0">
                    <div class="max-h-72 space-y-2 overflow-y-auto pr-1">
                        <div
                            v-for="studentPaymentReceipt in parsedReceipts"
                            :key="studentPaymentReceipt.id"
                            class="rounded-md border border-gray-200 bg-white px-3 py-2 text-xs shadow-sm"
                        >
                            <div class="flex items-center justify-between gap-3">
                                <div class="font-medium text-accent-foreground">
                                    {{ formatReceiptDate(studentPaymentReceipt.attributes.transactionDate) }}
                                </div>
                                <span
                                    :class="
                                        studentPaymentReceipt.credit > 0
                                            ? 'bg-emerald-100 text-emerald-700'
                                            : 'bg-rose-100 text-rose-700'
                                    "
                                    class="rounded-full px-2 py-0.5 text-[10px] font-semibold uppercase"
                                >
                                    {{ studentPaymentReceipt.credit > 0 ? 'credit' : 'debit' }}
                                </span>
                            </div>
                            <div class="mt-1 truncate text-[11px] text-gray-600">
                                {{
                                    studentPaymentReceipt.attributes.narration ||
                                    studentPaymentReceipt.attributes.description ||
                                    studentPaymentReceipt.attributes.transactionDetails
                                }}
                            </div>
                            <div class="mt-2 flex items-center justify-between gap-2 text-[11px]">
                                <div class="truncate text-gray-500">
                                    Ref:
                                    {{
                                        studentPaymentReceipt.attributes.reference ||
                                        studentPaymentReceipt.attributes.transactionId ||
                                        '---'
                                    }}
                                </div>
                                <div class="font-semibold text-accent-foreground">
                                    {{
                                        formatMoney(
                                            studentPaymentReceipt.credit > 0
                                                ? studentPaymentReceipt.credit
                                                : studentPaymentReceipt.debit,
                                            studentPaymentReceipt.attributes.isoCurrencyCode,
                                        )
                                    }}
                                </div>
                            </div>
                        </div>
                    </div>
                </template>
                <template v-else>
                    <Empty :message="$t('finance.no_receipts_found')" />
                </template>
            </template>
        </div>
    </BaseCard>
</template>
