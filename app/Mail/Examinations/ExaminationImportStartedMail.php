<?php

namespace App\Mail\Examinations;

use App\Models\Examinations\ExaminationImport;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class ExaminationImportStartedMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public ExaminationImport $import) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: __('examinations.import_started_subject', [
                'filename' => $this->import->original_filename,
            ]),
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'emails.examinations.import-started',
            with: [
                'import' => $this->import,
                'filename' => $this->import->original_filename,
                'source' => $this->import->source->label(),
            ],
        );
    }
}
