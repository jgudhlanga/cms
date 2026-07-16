<?php

namespace App\Mail\Examinations;

use App\Models\Examinations\ExaminationImport;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class ExaminationImportCompletedMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public ExaminationImport $import) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: __('examinations.import_completed_subject', [
                'filename' => $this->import->original_filename,
                'status' => $this->import->status->label(),
            ]),
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'emails.examinations.import-completed',
            with: [
                'import' => $this->import,
                'filename' => $this->import->original_filename,
                'status' => $this->import->status->label(),
                'rowsTotal' => $this->import->rows_total,
                'rowsProcessed' => $this->import->rows_processed,
                'rowsUpserted' => $this->import->rows_upserted,
                'rowsFailed' => $this->import->rows_failed,
                'errorMessage' => $this->import->error_message,
            ],
        );
    }
}
