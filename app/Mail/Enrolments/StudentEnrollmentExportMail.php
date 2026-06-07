<?php

declare(strict_types=1);

namespace App\Mail\Enrolments;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Storage;

class StudentEnrollmentExportMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        protected string $reportPath,
        protected ?string $intakeYear = null,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Student enrollment export completed',
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'emails.enrolments.student-enrollment-export',
            with: [
                'reportPath' => $this->reportPath,
                'intakeYear' => $this->intakeYear,
            ],
        );
    }

    /**
     * @return array<int, Attachment>
     */
    public function attachments(): array
    {
        if ($this->reportPath === '' || ! Storage::disk('local')->exists($this->reportPath)) {
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
