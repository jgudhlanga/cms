<script setup lang="ts">
import { useUtils } from '@/composables/core/useUtils';
import { useStudentsFinancials } from '@/composables/finance/useStudentsFinancials';
import { computed, onMounted } from 'vue';
import moment from 'moment';
import { Enrolment } from '@/types/enrolments';

interface Props {
    studentId: string;
    enrolment: Enrolment;
}
const props = defineProps<Props>();

const { formatCurrency } = useUtils();
const { getStudentFinancials, isLoading, studentPaymentReceipts } = useStudentsFinancials();

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
    const currencyPrefix = code.toUpperCase() === 'USD' ? 'USD ' : `${code} `;
    return `${currencyPrefix}${formatCurrency(String(amount))}`;
};

const originalAmountNearReference = (receipt: (typeof parsedReceipts.value)[number]): string | null => {
    const attributes = receipt.attributes as {
        usdConversionRate?: string | null;
        usdConversionRateDate?: string | null;
        originalAmountCredit?: string | number | null;
        originalAmountDebit?: string | number | null;
        originalIsoCurrencyCode?: string | null;
    };

    if (!attributes.usdConversionRate || !attributes.usdConversionRateDate || !attributes.originalIsoCurrencyCode) {
        return null;
    }

    const credit = toAmount(attributes.originalAmountCredit);
    const debit = toAmount(attributes.originalAmountDebit);
    const originalAmount = credit > 0 ? credit : debit;

    if (originalAmount <= 0) {
        return null;
    }

    const parsedDate = moment(attributes.usdConversionRateDate, ['YYYY-MM-DD', moment.ISO_8601], true);
    if (!parsedDate.isValid()) {
        return null;
    }

    const formattedAmount = formatCurrency(String(originalAmount));
    const amountWithSymbol = formattedAmount.includes('$') ? formattedAmount : `$${formattedAmount}`;

    return `${attributes.originalIsoCurrencyCode.toUpperCase()} ${amountWithSymbol} | ${parsedDate.format('YYYY-MM-DD')} | @ ${attributes.usdConversionRate}`;
};

const isUsdAmount = (receipt: (typeof parsedReceipts.value)[number]): boolean => {
    const currencyCode = (receipt.attributes.isoCurrencyCode || '').toString().toUpperCase();

    return currencyCode === 'USD';
};

const escapeRegex = (value: string): string => value.replace(/[.*+?^${}()|[\]\\]/g, '\\$&');

const deduplicateAdjacentPhraseBlocks = (value: string): string => {
    let normalized = value;

    for (let attempt = 0; attempt < 3; attempt++) {
        const next = normalized.replace(/\b([A-Za-z0-9][A-Za-z0-9&\-]*(?:\s+[A-Za-z0-9&\-]+){2,})\s+\1\b/gi, '$1');

        if (next === normalized) {
            break;
        }

        normalized = next;
    }

    return normalized;
};

const sanitizeReceiptDescription = (receipt: (typeof parsedReceipts.value)[number]): string => {
    const rawDescription =
        receipt.attributes.narration || receipt.attributes.description || receipt.attributes.transactionDetails || '';
    const normalizedRawDescription = String(rawDescription).trim();
    const studentName = String(props.enrolment?.attributes?.studentName || '').trim();
    const studentNumber = String(props.enrolment?.attributes?.studentNumber || '').trim();
    const referenceValue = String(receipt.attributes.reference || receipt.attributes.transactionId || '').trim();

    if (!normalizedRawDescription) {
        return '---';
    }

    const studentNamePattern = studentName ? new RegExp(escapeRegex(studentName), 'gi') : null;
    const studentNumberPattern = studentNumber ? new RegExp(escapeRegex(studentNumber), 'gi') : null;
    const referencePattern = referenceValue ? new RegExp(escapeRegex(referenceValue), 'gi') : null;
    const nameParts = studentName.split(/\s+/).filter(Boolean);
    const firstName = nameParts[0] ?? '';
    const lastName = nameParts.length > 1 ? nameParts[nameParts.length - 1] : '';
    const firstToLastSpanPattern =
        firstName && lastName ? new RegExp(`${escapeRegex(firstName)}[\\s\\S]*?${escapeRegex(lastName)}`, 'gi') : null;
    const firstNamePattern = firstName ? new RegExp(`\\b${escapeRegex(firstName)}\\b`, 'gi') : null;
    const lastNamePattern = lastName ? new RegExp(`\\b${escapeRegex(lastName)}\\b`, 'gi') : null;

    const redactedDescription = normalizedRawDescription
        .replace(referencePattern ?? /$^/, '')
        .replace(firstToLastSpanPattern ?? /$^/, '')
        .replace(firstNamePattern ?? /$^/, '')
        .replace(lastNamePattern ?? /$^/, '')
        .replace(studentNamePattern ?? /$^/, '')
        .replace(studentNumberPattern ?? /$^/, '')
        .replace(/\b(student[\s_-]*number|studentnumber)\s*[:=]?\s*[A-Za-z0-9/-]+\b/gi, '')
        .replace(/\b(ref(erence)?(\s*(no|number))?)\s*[:=]?\s*[A-Za-z0-9/-]+\b/gi, '')
        .replace(/\b(student[\s_-]*name|name)\s*[:=]?\s*[A-Za-z][A-Za-z' -]{1,60}\b/gi, '')
        .replace(/\bSTU[-/ ]?\d+\b/gi, '')
        .replace(/\s*\/100\b/gi, '')
        .replace(/\b100\b/g, '')
        .replace(/\|+/g, ' ')
        .replace(/\s{2,}/g, ' ')
        .replace(/^[,;:.\-\s|]+|[,;:.\-\s|]+$/g, '')
        .trim();

    const deduplicatedDescription = deduplicateAdjacentPhraseBlocks(redactedDescription)
        .replace(/\s{2,}/g, ' ')
        .trim();

    return deduplicatedDescription || normalizedRawDescription;
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
    await getStudentFinancials(props.studentId);
});

</script>

<template>
    <BaseCard :title="$t('finance.receipt')" color-variant="green-500">
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
                                    {{
                                        studentPaymentReceipt.credit > 0
                                            ? $t('finance.credit')
                                            : $t('finance.debit')
                                    }}
                                </span>
                            </div>
                            <div class="mt-1 whitespace-normal wrap-break-word text-[10px] leading-tight text-gray-600">
                                {{ sanitizeReceiptDescription(studentPaymentReceipt) }}
                            </div>
                            <div class="mt-2 text-[11px]">
                                <div class="text-gray-500">
                                    {{ $t('finance.reference') }}:
                                    {{
                                        studentPaymentReceipt.attributes.reference ||
                                        studentPaymentReceipt.attributes.transactionId ||
                                        $t('finance.not_available')
                                    }}
                                    <template v-if="originalAmountNearReference(studentPaymentReceipt)">
                                        <div class="mt-1">
                                            {{ originalAmountNearReference(studentPaymentReceipt) }}
                                        </div>
                                    </template>
                                </div>
                                <div class="mt-1 flex justify-end">
                                    <span
                                        :class="
                                            isUsdAmount(studentPaymentReceipt)
                                                ? 'bg-emerald-100 text-emerald-700'
                                                : 'bg-slate-100 text-slate-700'
                                        "
                                        class="rounded-full px-2 py-0.5 text-[10px] font-semibold"
                                    >
                                        {{
                                            formatMoney(
                                                studentPaymentReceipt.credit > 0
                                                    ? studentPaymentReceipt.credit
                                                    : studentPaymentReceipt.debit,
                                                studentPaymentReceipt.attributes.isoCurrencyCode,
                                            )
                                        }}
                                    </span>
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
