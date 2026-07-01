<?php

declare(strict_types=1);

return [
    /*
    |--------------------------------------------------------------------------
    | Bulk finalise enrolments
    |--------------------------------------------------------------------------
    |
    | queue_retry_after must be greater than job_timeout so the database queue
    | driver does not release a long-running bulk finalise job back to the queue
    | while it is still processing.
    |
    */
    'bulk_finalise' => [
        'job_timeout' => 3600,
        'job_tries' => 1,
        'run_cache_ttl_seconds' => 7200,
        'summary_cache_ttl_seconds' => 300,
        'payment_match_single_query_threshold' => 10,
        'payment_match_statement_chunk_size' => 500,
        'queue_retry_after' => 3900,
    ],
];
