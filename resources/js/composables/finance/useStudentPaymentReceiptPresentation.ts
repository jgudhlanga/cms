import { useUtils } from '@/composables/core/useUtils';
import type { ParsedStudentPaymentReceipt, StudentPaymentReceipt } from '@/types/finance';
import { trans } from 'laravel-vue-i18n';
import moment from 'moment';
import { type MaybeRefOrGetter, computed, toValue } from 'vue';

export type StudentPaymentReceiptContext = {
    studentName: string;
    studentNumber: string;
};

export const toAmount = (value?: string | number | null): number => {
    if (value === null || value === undefined || value === '') {
        return 0;
    }

    const parsed = Number(value);
    return Number.isFinite(parsed) ? parsed : 0;
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

export function useStudentPaymentReceiptPresentation(context: MaybeRefOrGetter<StudentPaymentReceiptContext>) {
    const { formatCurrency } = useUtils();

    const notAvailable = () => trans('finance.not_available');

    const formatMoney = (value: string | number | null | undefined, currencyCode?: string): string => {
        const amount = toAmount(value);
        const code = currencyCode || 'USD';
        const currencyPrefix = code.toUpperCase() === 'USD' ? 'USD ' : `${code} `;
        return `${currencyPrefix}${formatCurrency(String(amount))}`;
    };

    const formatReceiptDate = (value?: string | null): string => {
        if (!value) {
            return notAvailable();
        }

        const parsedDate = moment(value, ['DD-MM-YY HH:mm:ss', 'DD-MM-YYYY HH:mm:ss', moment.ISO_8601], true);
        return parsedDate.isValid() ? parsedDate.format('ll') : notAvailable();
    };

    const sanitizeReceiptDescription = (receipt: StudentPaymentReceipt | ParsedStudentPaymentReceipt): string => {
        const rawDescription =
            receipt.attributes.narration ||
            receipt.attributes.description ||
            receipt.attributes.transactionDetails ||
            '';
        const normalizedRawDescription = String(rawDescription).trim();
        const ctx = toValue(context);
        const studentName = String(ctx.studentName || '').trim();
        const studentNumber = String(ctx.studentNumber || '').trim();
        const referenceValue = String(receipt.attributes.reference || receipt.attributes.transactionId || '').trim();

        if (!normalizedRawDescription) {
            return notAvailable();
        }

        const studentNamePattern = studentName ? new RegExp(escapeRegex(studentName), 'gi') : null;
        const studentNumberPattern = studentNumber ? new RegExp(escapeRegex(studentNumber), 'gi') : null;
        const referencePattern = referenceValue ? new RegExp(escapeRegex(referenceValue), 'gi') : null;
        const nameParts = studentName.split(/\s+/).filter(Boolean);
        const firstName = nameParts[0] ?? '';
        const lastName = nameParts.length > 1 ? nameParts[nameParts.length - 1] : '';
        const firstToLastSpanPattern =
            firstName && lastName
                ? new RegExp(`${escapeRegex(firstName)}[\\s\\S]*?${escapeRegex(lastName)}`, 'gi')
                : null;
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

    const originalAmountNearReference = (receipt: StudentPaymentReceipt | ParsedStudentPaymentReceipt): string | null => {
        const attributes = receipt.attributes;

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

    const isUsdAmount = (receipt: StudentPaymentReceipt | ParsedStudentPaymentReceipt): boolean => {
        const currencyCode = (receipt.attributes.isoCurrencyCode || '').toString().toUpperCase();

        return currencyCode === 'USD';
    };

    const receiptReference = (receipt: StudentPaymentReceipt | ParsedStudentPaymentReceipt): string => {
        return (
            receipt.attributes.reference ||
            receipt.attributes.transactionId ||
            notAvailable()
        );
    };

    const parseReceipts = (receipts: StudentPaymentReceipt[]): ParsedStudentPaymentReceipt[] =>
        receipts.map((receipt) => ({
            ...receipt,
            credit: toAmount(receipt.attributes.amountCredit),
            debit: toAmount(receipt.attributes.amountDebit),
        }));

    const formatReceiptBalance = (value?: string | null): string => {
        const trimmed = String(value ?? '').trim();
        if (!trimmed) {
            return notAvailable();
        }
        return trimmed;
    };

    const emptyAmount = () => trans('finance.empty_amount');

    const formatLedgerDate = (value?: string | null): string => {
        if (!value) {
            return notAvailable();
        }

        const parsedDate = moment(value, ['DD-MM-YY HH:mm:ss', 'DD-MM-YYYY HH:mm:ss', moment.ISO_8601], true);
        return parsedDate.isValid() ? parsedDate.format('YYYY-MM-DD') : notAvailable();
    };

    const formatUsdDisplay = (formatted: string): string => {
        if (formatted.startsWith('-$')) {
            return `-USD$${formatted.slice(2)}`;
        }

        if (formatted.startsWith('$')) {
            return `USD$${formatted.slice(1)}`;
        }

        return formatted.startsWith('-') ? `-USD$${formatted.slice(1)}` : `USD$${formatted}`;
    };

    const formatUsdAmount = (amount: string | number): string => {
        return formatUsdDisplay(formatCurrency(String(toAmount(amount))));
    };

    const formatLedgerUsdAmount = (amount: number): string => {
        if (amount <= 0) {
            return emptyAmount();
        }

        return formatUsdAmount(amount);
    };

    const isChargeEntry = (receipt: ParsedStudentPaymentReceipt): boolean => {
        if (receipt.debit > 0) {
            return true;
        }

        return String(receipt.attributes.debitCreditFlag || '').toUpperCase() === 'D';
    };

    const isPaymentEntry = (receipt: ParsedStudentPaymentReceipt): boolean => {
        if (receipt.credit > 0) {
            return true;
        }

        return String(receipt.attributes.debitCreditFlag || '').toUpperCase() === 'C';
    };

    const formatRunningBalance = (receipt: ParsedStudentPaymentReceipt): string => {
        const balance = receipt.attributes.runningBalance ?? receipt.attributes.clearedRunningBalance;
        const numeric = toAmount(balance);

        if (numeric > 0 || (balance && String(balance).trim() !== '')) {
            return formatLedgerUsdAmount(numeric > 0 ? numeric : toAmount(balance));
        }

        return notAvailable();
    };

    return {
        formatMoney,
        formatReceiptDate,
        formatLedgerDate,
        formatUsdAmount,
        formatLedgerUsdAmount,
        sanitizeReceiptDescription,
        originalAmountNearReference,
        isUsdAmount,
        isChargeEntry,
        isPaymentEntry,
        receiptReference,
        parseReceipts,
        formatReceiptBalance,
        formatRunningBalance,
        emptyAmount,
        notAvailable,
    };
}

export function useParsedStudentPaymentReceipts(
    receipts: MaybeRefOrGetter<StudentPaymentReceipt[]>,
    context: MaybeRefOrGetter<StudentPaymentReceiptContext>,
) {
    const { parseReceipts } = useStudentPaymentReceiptPresentation(context);

    return computed(() => parseReceipts(toValue(receipts)));
}
