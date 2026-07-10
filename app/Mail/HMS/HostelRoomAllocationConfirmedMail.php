<?php

namespace App\Mail\HMS;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class HostelRoomAllocationConfirmedMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        protected string $name,
        protected string $hostelName,
        protected string $roomName,
        protected ?string $sectionName,
        protected ?string $floorNumber,
        protected ?string $roomType,
        protected string $checkIn,
        protected string $checkOut,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: __('hms.hostel_room_allocation_confirmed_subject'),
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'emails.hms.room-allocation-confirmed',
            with: [
                'name' => $this->name,
                'hostelName' => $this->hostelName,
                'roomName' => $this->roomName,
                'sectionName' => $this->sectionName,
                'floorNumber' => $this->floorNumber,
                'roomType' => $this->roomType,
                'checkIn' => $this->checkIn,
                'checkOut' => $this->checkOut,
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
