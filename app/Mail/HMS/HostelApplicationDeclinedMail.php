<?php

namespace App\Mail\HMS;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class HostelApplicationDeclinedMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        protected string $name,
        protected string $declineReason,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: __('hms.hostel_application_declined_subject'),
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'emails.hms.application-declined',
            with: [
                'name' => $this->name,
                'declineReason' => $this->declineReason,
            ],
        );
    }

    /**
     * @return array<int, Attachment>
     */
    public function attachments(): array
    {
        return [];
    }
}
