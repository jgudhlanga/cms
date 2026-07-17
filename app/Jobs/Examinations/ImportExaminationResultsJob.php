<?php

namespace App\Jobs\Examinations;

use App\Enums\Examinations\ExaminationImportStatusEnum;
use App\Mail\Examinations\ExaminationImportCompletedMail;
use App\Models\Examinations\ExaminationImport;
use App\Services\Examinations\ExaminationImportService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Mail;
use Throwable;

class ImportExaminationResultsJob implements ShouldQueue
{
    use Queueable;

    public int $tries;

    public int $timeout;

    public function __construct(public int $examinationImportId)
    {
        $this->tries = max(1, (int) config('examinations.job_tries', 3));
        $this->timeout = max(60, (int) config('examinations.job_timeout', 300));
        $this->onConnection((string) config('examinations.queue_connection', 'database'));
        $this->onQueue((string) config('examinations.queue', 'exams'));
    }

    public function handle(ExaminationImportService $service): void
    {
        $import = ExaminationImport::query()->withoutGlobalScopes()->findOrFail($this->examinationImportId);

        $service->processImport($import);

        $import->refresh();

        if ($import->status === ExaminationImportStatusEnum::Cancelled) {
            return;
        }

        $recipients = $service->notifyRecipients($import->starter);

        if ($recipients !== []) {
            Mail::to($recipients)->queue(
                (new ExaminationImportCompletedMail($import))
                    ->onConnection((string) config('examinations.queue_connection', 'database'))
                    ->onQueue((string) config('examinations.queue', 'exams'))
            );
        }
    }

    public function failed(?Throwable $exception): void
    {
        $import = ExaminationImport::query()->withoutGlobalScopes()->find($this->examinationImportId);

        if ($import === null || $exception === null) {
            return;
        }

        app(ExaminationImportService::class)->markFailed($import, $exception);

        $import->refresh();
        $service = app(ExaminationImportService::class);
        $recipients = $service->notifyRecipients($import->starter);

        if ($recipients !== []) {
            Mail::to($recipients)->queue(
                (new ExaminationImportCompletedMail($import))
                    ->onConnection((string) config('examinations.queue_connection', 'database'))
                    ->onQueue((string) config('examinations.queue', 'exams'))
            );
        }
    }
}
