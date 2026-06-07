<?php

declare(strict_types=1);

namespace App\Jobs\Applications;

use App\Services\Applications\ApplicationExportService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class ExportApplicationJob implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public readonly ?string $intakeYear = null,
        public readonly array $recipientEmails = [],
    ) {}

    public function handle(ApplicationExportService $exportService): string
    {
        return $exportService->export($this->intakeYear, $this->recipientEmails);
    }
}
