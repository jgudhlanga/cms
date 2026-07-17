<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Dump inbox / processing folders
    |--------------------------------------------------------------------------
    |
    | Relative paths on the local disk (storage/app). The examinations:watch
    | command monitors the inbox for new .xlsx/.xls/.csv dumps.
    |
    */

    // Empty env values must not override defaults (env('KEY', 'default') returns '' when KEY=).
    'inbox_path' => env('EXAMINATIONS_INBOX_PATH') ?: 'examinations/inbox',

    'processing_path' => env('EXAMINATIONS_PROCESSING_PATH') ?: 'examinations/processing',

    'processed_path' => env('EXAMINATIONS_PROCESSED_PATH') ?: 'examinations/processed',

    'failed_path' => env('EXAMINATIONS_FAILED_PATH') ?: 'examinations/failed',

    /*
    |--------------------------------------------------------------------------
    | Uploads (UI)
    |--------------------------------------------------------------------------
    */

    'uploads_path' => env('EXAMINATIONS_UPLOADS_PATH') ?: 'examinations/uploads',

    /*
    |--------------------------------------------------------------------------
    | Import chunking / job
    |--------------------------------------------------------------------------
    */

    'chunk_size' => (int) env('EXAMINATIONS_IMPORT_CHUNK_SIZE', 500),

    /*
    | Align with production Supervisor worker-exams:
    | queue:work database --queue=exams --tries=3 --timeout=300
    */
    'job_timeout' => (int) (env('EXAMINATIONS_IMPORT_JOB_TIMEOUT') ?: 300),

    'job_tries' => (int) (env('EXAMINATIONS_IMPORT_JOB_TRIES') ?: 3),

    'queue' => env('EXAMINATIONS_IMPORT_JOB_DISPATCH_QUEUE') ?: 'exams',

    'queue_connection' => env('EXAMINATIONS_IMPORT_JOB_DISPATCH_QUEUE_CONNECTION') ?: 'database',

    /*
    |--------------------------------------------------------------------------
    | Watcher write-settle debounce
    |--------------------------------------------------------------------------
    |
    | Wait until the file size is unchanged for this many seconds before
    | dispatching an import (avoids reading a partial copy).
    |
    */

    'watcher_settle_seconds' => (int) env('EXAMINATIONS_WATCHER_SETTLE_SECONDS', 3),

    'watcher_settle_max_wait_seconds' => (int) env('EXAMINATIONS_WATCHER_SETTLE_MAX_WAIT', 120),

    /*
    |--------------------------------------------------------------------------
    | Notify emails
    |--------------------------------------------------------------------------
    |
    | Comma-separated list. Upload imports notify both the uploading user and
    | these addresses, with duplicates removed. When empty, watcher imports
    | notify users with import:examinations and an email address.
    |
    */

    'notify' => array_values(array_filter(array_map(
        'trim',
        explode(',', (string) env('EXAMINATIONS_NOTIFY', '')),
    ))),

    'allowed_extensions' => ['xlsx', 'xls', 'csv'],

];
