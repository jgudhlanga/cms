export type Ledger = {
    type: string;
    id: string | number;
    attributes: {
        feeTypeId: number | null;
        feeType: string | null;
        paymentOption: number | null;
        type: string | null;
        paymentStatus: string | null;
        amount: string | number | null;
        currency: string | null;
        clientFee: string | number | null;
        merchantFee: string | number | null;
        systemReference: string | null;
        paymentReference: string | null;
        dueDate: string | null;
        paymentDate: string | null;
        responseMessage: string | null;
        responseCode: string | null;
        levelId: number | null;
        level: string | null;
        studentApplicationId: number | null;
        createdAt: string | null;
        updatedAt: string | null;
        deletedAt: string | null;
    };
};

export type LedgerEmailSearchTypeOption = {
    value: string;
    label: string;
};

export type LedgerEmailSearchTypeSelectionResponse = {
    requiresTypeSelection: true;
    types: LedgerEmailSearchTypeOption[];
};

export type PaymentGatewayFieldSource = 'database' | 'env' | 'missing';

export type PaymentGatewayPublicField = {
    value: string;
    source: PaymentGatewayFieldSource;
};

export type PaymentGatewaySecretField = {
    isSet: boolean;
    source: PaymentGatewayFieldSource;
};

export type PaymentGatewayDisplay = {
    gateway_name: PaymentGatewayPublicField;
    gateway_base_url: PaymentGatewayPublicField;
    gateway_api_key: PaymentGatewaySecretField;
    gateway_secret: PaymentGatewaySecretField;
    bank_statements_base_url: PaymentGatewayPublicField;
    usd_account_number: PaymentGatewayPublicField;
    usd_password: PaymentGatewaySecretField;
    zwg_account_number: PaymentGatewayPublicField;
    zwg_password: PaymentGatewaySecretField;
    income_gen_account_number: PaymentGatewayPublicField;
    income_gen_password: PaymentGatewaySecretField;
    updated_at: string | null;
};

