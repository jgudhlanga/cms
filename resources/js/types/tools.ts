export interface PaymentCheckResponse {
    status?: string;
    message?: string;
    amount?: string | number;
    clientFee?: number | string;
    createdDate?: string;
    currency?: string;
    itemName?: string;
    merchantFee?: number | string;
    merchantId?: string;
    orderReference?: string;
    paymentOption?: string;
    reference?: string;
    resultUrl?: string;
    returnUrl?: string;
}
