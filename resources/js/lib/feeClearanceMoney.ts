export type FeeClearanceBankConversion = {
    originalAmount: number;
    originalCurrency: string;
    usdAmount: number;
    rate: string;
    label: string;
    date: string;
};

/**
 * Fee-clearance amounts are USD aggregates; display as USD$330.00.
 */
export function formatFeeClearanceUsd(value: number | string | null | undefined): string {
    const amount = Number(value ?? 0);
    const formatted = Number.isFinite(amount)
        ? amount.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 })
        : '0.00';

    return `USD$${formatted}`;
}

export function formatFeeClearanceZwgAmount(value: number | string | null | undefined): string {
    const amount = Number(value ?? 0);
    if (!Number.isFinite(amount)) {
        return '0.00';
    }

    return amount.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
}
