<?php

namespace App\Mail\Enrolments;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Storage;

class BulkFinaliseEnrolmentsReportMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        protected int $successfulFinalised,
        protected int $failedFinalisations,
        protected string $startDate,
        protected string $endDate,
        protected ?string $reportPath,
        protected bool $isDryRun = false,
    ) {}

    public function envelope(): Envelope
    {
        $prefix = $this->isDryRun ? '[DRY RUN] ' : '';

        return new Envelope(
            subject: "{$prefix}Bulk enrolment finalisation report (failed finalisations: {$this->failedFinalisations})",
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'emails.enrolments.bulk-finalise-report',
            with: [
                'successfulFinalised' => $this->successfulFinalised,
                'failedFinalisations' => $this->failedFinalisations,
                'startDate' => $this->startDate,
                'endDate' => $this->endDate,
                'reportPath' => $this->reportPath,
                'isDryRun' => $this->isDryRun,
            ],
        );
    }

    /**
     * @return array<int, Attachment>
     */
    public function attachments(): array
    {
        if (! $this->isDryRun && ! app()->environment('production')) {
            return [];
        }

        if (! is_string($this->reportPath) || $this->reportPath === '') {
            return [];
        }

        if (! Storage::disk('local')->exists($this->reportPath)) {
            return [];
        }

        return [
            Attachment::fromData(
                fn () => Storage::disk('local')->get($this->reportPath),
                basename($this->reportPath),
            )->withMime('text/csv'),
        ];
    }
}
