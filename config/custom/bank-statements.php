<?php

return [
    'base_url' => env('BANK_STATEMENTS_BASE_URL'),
    'connect_timeout' => (float) env('BANK_STATEMENTS_CONNECT_TIMEOUT', 10),
    'timeout' => (float) env('BANK_STATEMENTS_TIMEOUT', 300),
    'retry_times' => (int) env('BANK_STATEMENTS_RETRY_TIMES', 3),
    'retry_sleep_ms' => (int) env('BANK_STATEMENTS_RETRY_SLEEP_MS', 250),
    'bank_statements_queue' => env('BANK_STATEMENTS_QUEUE', 'bank-statements'),
    'account_types' => ['usd', 'zwg', 'income-gen'],
    'chunk_days' => max(1, (int) env('BANK_STATEMENTS_CHUNK_DAYS', 14)),
    'dispatch_limit' => max(1, (int) env('BANK_STATEMENTS_DISPATCH_LIMIT', 100)),
    'processing_stale_minutes' => max(1, (int) env('BANK_STATEMENTS_PROCESSING_STALE_MINUTES', 45)),
    'plan_insert_chunk' => max(100, (int) env('BANK_STATEMENTS_PLAN_INSERT_CHUNK', 500)),
    'plan_anchor_start' => (string) env('BANK_STATEMENTS_PLAN_ANCHOR_START', '2025-11-01'),
    'bank_statements_connect_timeout' => (float) env('BANK_STATEMENTS_CONNECT_TIMEOUT', 10),
    'bank_statements_timeout' => (float) env('BANK_STATEMENTS_TIMEOUT', 300),
    'bank_statements_retry_times' => (int) env('BANK_STATEMENTS_RETRY_TIMES', 3),
    'bank_statements_retry_sleep_ms' => (int) env('BANK_STATEMENTS_RETRY_SLEEP_MS', 250),
    'zwg' => [
        'account_number' => env('ACCOUNT_NUMBER_ZWG'),
        'password' => env('ACCOUNT_NUMBER_ZWG_PASSWORD'),
    ],
    'usd' => [
        'account_number' => env('ACCOUNT_NUMBER_USD'),
        'password' => env('ACCOUNT_NUMBER_USD_PASSWORD'),
    ],
    'income-gen' => [
        'account_number' => env('ACCOUNT_NUMBER_INCOME_GEN'),
        'password' => env('ACCOUNT_NUMBER_INCOME_GEN_PASSWORD'),
    ],
];
