<?php

namespace App\Services\Integrations\Banks\ZB;

final readonly class FetchBankStatementExecuteResult
{
    /**
     * @param  bool  $resetWindowToPendingForRetry  True when the API returned HTTP 401 so the job can re-queue the window for a later dispatch.
     */
    public function __construct(
        public int $exitCode,
        public int $persistFailedCount,
        public bool $resetWindowToPendingForRetry = false,
    ) {}
}
