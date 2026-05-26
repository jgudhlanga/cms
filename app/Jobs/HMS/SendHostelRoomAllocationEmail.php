<?php

namespace App\Jobs\HMS;

use App\Mail\HMS\HostelRoomAllocationConfirmedMail;
use App\Models\HMS\HostelApplication;
use App\Models\HMS\HostelRoomAllocation;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Mail;

class SendHostelRoomAllocationEmail implements ShouldQueue
{
    use Queueable;

    public function __construct(protected int $hostelApplicationId) {}

    public function handle(): void
    {
        $application = HostelApplication::query()
            ->with(['student.user'])
            ->find($this->hostelApplicationId);

        if ($application === null) {
            return;
        }

        $email = $application->student?->user?->email ?? $application->email_address;

        if (blank($email)) {
            return;
        }

        $allocation = HostelRoomAllocation::query()
            ->with(['room.hostel'])
            ->where('student_id', $application->student_id)
            ->active()
            ->latest('id')
            ->first();

        $room = $allocation?->room;
        $hostel = $room?->hostel;

        if ($allocation === null || $room === null || $hostel === null) {
            return;
        }

        $name = $application->student?->user?->full_name
            ?? $application->name
            ?? 'Applicant';

        $dateFormat = (string) config('app.date_format', 'd M Y');

        Mail::to($email)->send(new HostelRoomAllocationConfirmedMail(
            $name,
            (string) $hostel->name,
            (string) $room->name,
            filled($room->floor_number) ? (string) $room->floor_number : null,
            $this->roomTypeLabel($room->room_type),
            $allocation->check_in?->format($dateFormat) ?? '',
            $allocation->check_out?->format($dateFormat) ?? '',
        ));
    }

    private function roomTypeLabel(?string $roomType): ?string
    {
        if (blank($roomType)) {
            return null;
        }

        $key = 'hms.room_type_'.$roomType;
        $label = __($key);

        return $label === $key ? (string) $roomType : $label;
    }
}
