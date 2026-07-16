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

    'job_timeout' => (int) env('EXAMINATIONS_IMPORT_JOB_TIMEOUT', 3600),

    'job_tries' => (int) env('EXAMINATIONS_IMPORT_JOB_TRIES', 1),

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
    | Notify emails (watcher imports; UI uses the uploading user)
    |--------------------------------------------------------------------------
    |
    | Comma-separated list. When empty, users with import:examinations and an
    | email address are notified for watcher-sourced imports.
    |
    */

    'notify' => array_values(array_filter(array_map(
        'trim',
        explode(',', (string) env('EXAMINATIONS_NOTIFY', '')),
    ))),

    'allowed_extensions' => ['xlsx', 'xls', 'csv'],

];
