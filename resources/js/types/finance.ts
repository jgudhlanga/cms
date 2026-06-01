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
        usdConversionRate?: string | null;
        usdConversionRateLabel?: string | null;
        usdConversionRateDate?: string | null;
        originalAmountCredit?: string | number | null;
        originalAmountDebit?: string | number | null;
        originalIsoCurrencyCode?: string | null;
        clearedRunningBalance: string;
        runningBalance?: string | null;
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

export type ParsedStudentPaymentReceipt = StudentPaymentReceipt & {
    credit: number;
    debit: number;
};

export type StudentLedgerSummary = {
    totalInvoiced: string;
    totalPayments: string;
    outstandingBalance: string;
    paidPercent: number;
};

export type StudentLedgerResponse = {
    data: StudentPaymentReceipt[];
    summary: StudentLedgerSummary;
};

export type FinanceTransactionQuery = {
    type: string;
    id: string | number;
    attributes: {
        studentId: string | number;
        studentName: string | null;
        studentNumber: string | null;
        idNumber: string | null;
        paymentReference: string;
        description: string | null;
        status: string;
        statusLabel: string;
        declineReason: string | null;
        bankStatementId: string | number | null;
        reconciledByName: string | null;
        declinedByName: string | null;
        reconciledAt: string | null;
        declinedAt: string | null;
        createdAt: string | null;
        updatedAt: string | null;
    };
};

export type StudentPaymentReceiptCollection = {
    data: StudentPaymentReceipt[];
    links: {
        first: string | null;
        last: string | null;
        prev: string | null;
        next: string | null;
    };
    meta: {
        current_page: number;
        last_page: number;
        per_page: number;
        total: number;
        from: number | null;
        to: number | null;
        path?: string | null;
        links?: Array<{ url: string | null; label: string | number; active: boolean }>;
    };
};
