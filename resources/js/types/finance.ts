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

