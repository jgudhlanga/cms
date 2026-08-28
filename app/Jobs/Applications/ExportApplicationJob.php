<?php

declare(strict_types=1);

namespace App\Jobs\Applications;

use App\Services\Applications\ApplicationExportService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class ExportApplicationJob implements ShouldQueue
{
    use Queueable;

    /**
     * @param  array<string, mixed>|string|null  $filters
     * @param  list<string>  $recipientEmails
     */
    public function __construct(
        public readonly array|string|null $filters = null,
        public readonly array $recipientEmails = [],
    ) {}

    public function handle(ApplicationExportService $exportService): string
    {
        return $exportService->export($this->filters, $this->recipientEmails);
    }
}
