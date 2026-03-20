<?php
return [
    'payment-gateway' => [
        'name' => env('PAYMENT_GATEWAY_NAME', 'smile-n-pay'),
        'base_url' => env('PAYMENT_GATEWAY_BASE_URL'),
        'api_key' => env('PAYMENT_GATEWAY_API_KEY'),
        'secret' => env('PAYMENT_GATEWAY_SECRET'),
        'return_url' => env('PAYMENT_GATEWAY_RETURN_URL'),
        'cancel_url' => env('PAYMENT_GATEWAY_CANCEL_URL'),
        'failure_url' => env('PAYMENT_GATEWAY_FAILURE_URL'),
        'result_url' => env('PAYMENT_GATEWAY_RESULT_URL'),
    ],
    'bank_payments_base_url' => env('BANK_PAYMENTS_BASE_URL'),
    'zwg' => [
        'institution_id' => env('BANK_PAYMENTS_ZWG_INSTITUTION_ID'),
        'password' => env('BANK_PAYMENTS_ZWG_PASSWORD'),
    ],
    'usd' => [
        'institution_id' => env('BANK_PAYMENTS_USD_INSTITUTION_ID'),
        'password' => env('BANK_PAYMENTS_USD_PASSWORD'),
    ],
    'income-gen' => [
        'institution_id' => env('BANK_PAYMENTS_INCOME_GEN_INSTITUTION_ID'),
        'password' => env('BANK_PAYMENTS_INCOME_GEN_PASSWORD'),
    ],
];
