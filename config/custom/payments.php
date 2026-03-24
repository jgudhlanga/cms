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
    'bank_payments_connect_timeout' => (float) env('BANK_PAYMENTS_CONNECT_TIMEOUT', 10),
    'bank_payments_timeout' => (float) env('BANK_PAYMENTS_TIMEOUT', 120),
    'bank_payments_retry_times' => (int) env('BANK_PAYMENTS_RETRY_TIMES', 3),
    'bank_payments_retry_sleep_ms' => (int) env('BANK_PAYMENTS_RETRY_SLEEP_MS', 250),
    'bank_payments_queue' => env('BANK_PAYMENTS_QUEUE', 'payments'),
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
