export type FinanceExchangeRate = {
    type?: string;
    id?: string;
    attributes: {
        date: string;
        currencyFrom: string;
        currencyTo: string;
        rate: string;
        createdAt?: string;
        updatedAt?: string;
        deletedAt?: string;
    };
};

export type FinanceExchangeRateParams = {
    date: string;
    currency_from: string;
    currency_to: string;
    rate: string;
};

export type StudentPaymentReceipt = {
    type: string;
    id: string | number;
    attributes: {
        tranNumberAsc: string;
        tranNumberDesc: string;
        transactionId: string;
        transactionSrId: string;
        transactionDate: string;
        narration: string;
        reference: string;
        code: string;
        description: string;
        debitCreditFlag: string;
        amountCredit: string;
        amountDebit: string;
        clearedRunningBalance: string;
        blockedBalance: string;
        debitLimit: string;
        creditLimit: string;
        isoCurrencyCode: string;
        accountDescription: string;
        ubfullName: string;
        pipeCount: string;
        pipe1: string | null;
        pipe2: string | null;
        pipe3: string | null;
        pipe4: string | null;
        pipe5: string | null;
        pipe6: string | null;
        pipe7: string | null;
        pipe8: string | null;
        pipe9: string | null;
        pipe10: string | null;
        pipe1Details: string | null;
        pipe2Details: string | null;
        pipe3Details: string | null;
        pipe4Details: string | null;
        pipe5Details: string | null;
        pipe6Details: string | null;
        pipe7Details: string | null;
        pipe8Details: string | null;
        pipe9Details: string | null;
        pipe10Details: string | null;
        transactionDetails: string;
        createdAt: string;
        updatedAt: string;
        deletedAt: string | null;
    };
};