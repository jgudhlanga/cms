<?php

namespace App\Events\HMS;

use App\Models\HMS\HostelRoom;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class HostelRoomOccupancySynced
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public HostelRoom $room,
        public int $previousOccupancy,
        public int $currentOccupancy,
    ) {}
}
