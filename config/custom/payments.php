<?php
return [
    'payment-gateway' => [
        'base_url' => env('SMILENPAY_BASE_URL'),
        'api_key' => env('SMILENPAY_API_KEY'),
        'secret' => env('SMILENPAY_SECRET'),
        'return_url' => env('SMILENPAY_RETURN_URL'),
        'cancel_url' => env('SMILENPAY_CANCEL_URL'),
        'failure_url' => env('SMILENPAY_FAILURE_URL'),
        'result_url' => env('SMILENPAY_RESULT_URL'),
    ],
];
