<?php

namespace App\Mail\HMS;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class HostelApplicationAwaitingPaymentMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        protected string $name,
        protected string $portalUrl,
        protected string $paymentDueDate,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: __('hms.hostel_application_awaiting_payment_subject'),
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'emails.hms.awaiting-payment',
            with: [
                'name' => $this->name,
                'portalUrl' => $this->portalUrl,
                'paymentDueDate' => $this->paymentDueDate,
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
