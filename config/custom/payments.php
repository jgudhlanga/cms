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
];
