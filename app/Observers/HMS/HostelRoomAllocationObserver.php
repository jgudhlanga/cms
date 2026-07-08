<?php

namespace App\Observers\HMS;

use App\Enums\HMS\HostelAllocationStatusEnum;
use App\Enums\HMS\HostelAllocationTypeEnum;
use App\Models\HMS\HostelRoom;
use App\Models\HMS\HostelRoomAllocation;
use App\Models\HMS\HostelRoomSection;
use Illuminate\Validation\ValidationException;

class HostelRoomAllocationObserver
{
    public function creating(HostelRoomAllocation $allocation): void
    {
        if ($allocation->type === null) {
            $allocation->type = HostelAllocationTypeEnum::DIRECT;
        }

        if ($allocation->status === null) {
            $allocation->status = HostelAllocationStatusEnum::ACTIVE;
        }

        $this->validateAllocation($allocation);
    }

    public function updating(HostelRoomAllocation $allocation): void
    {
        if ($allocation->check_out !== null
            && $allocation->getOriginal('check_out') === null
            && $allocation->status === HostelAllocationStatusEnum::ACTIVE) {
            $allocation->status = HostelAllocationStatusEnum::CHECKED_OUT;
        }

        $this->validateAllocation($allocation);
    }

    public function created(HostelRoomAllocation $allocation): void
    {
        $this->syncRoom($allocation);
    }

    public function updated(HostelRoomAllocation $allocation): void
    {
        $this->syncRoom($allocation);

        if ($allocation->wasChanged('hostel_room_id')) {
            $previousRoomId = $allocation->getOriginal('hostel_room_id');
            if ($previousRoomId !== null && (int) $previousRoomId !== (int) $allocation->hostel_room_id) {
                HostelRoom::query()
                    ->find($previousRoomId)
                    ?->syncOccupancyFromAllocations();
            }
        }
    }

    public function deleted(HostelRoomAllocation $allocation): void
    {
        $this->syncRoom($allocation);
    }

    public function restored(HostelRoomAllocation $allocation): void
    {
        $this->validateAllocation($allocation);
        $this->syncRoom($allocation);
    }

    private function validateAllocation(HostelRoomAllocation $allocation): void
    {
        if (! $allocation->isActive()) {
            return;
        }

        $room = $allocation->room()->first();
        if ($room === null) {
            throw ValidationException::withMessages([
                'hostel_room_id' => [__('hms.room_not_found')],
            ]);
        }

        $this->validateSectionBelongsToRoom($allocation, $room);
        $this->validateSectionNotOccupied($allocation);

        $activeOnRoom = $room->allocations()
            ->active()
            ->when($allocation->exists, fn ($q) => $q->where('id', '!=', $allocation->id))
            ->count();

        if ($activeOnRoom >= (int) $room->max_occupancy) {
            throw ValidationException::withMessages([
                'hostel_room_id' => [__('hms.room_at_capacity')],
            ]);
        }

        $studentAlreadyAssigned = HostelRoomAllocation::query()
            ->active()
            ->where('student_id', $allocation->student_id)
            ->when($allocation->exists, fn ($q) => $q->where('id', '!=', $allocation->id))
            ->exists();

        if ($studentAlreadyAssigned) {
            throw ValidationException::withMessages([
                'student_id' => [__('hms.student_already_allocated')],
            ]);
        }
    }

    private function validateSectionBelongsToRoom(HostelRoomAllocation $allocation, HostelRoom $room): void
    {
        if ($allocation->hostel_room_section_id === null) {
            return;
        }

        $section = HostelRoomSection::query()->find($allocation->hostel_room_section_id);

        if ($section === null) {
            throw ValidationException::withMessages([
                'hostel_room_section_id' => [__('hms.room_section_not_found')],
            ]);
        }

        if ((int) $section->hostel_room_id !== (int) $room->id) {
            throw ValidationException::withMessages([
                'hostel_room_section_id' => [__('hms.room_section_mismatch')],
            ]);
        }
    }

    private function validateSectionNotOccupied(HostelRoomAllocation $allocation): void
    {
        if ($allocation->hostel_room_section_id === null) {
            return;
        }

        $sectionAlreadyOccupied = HostelRoomAllocation::query()
            ->active()
            ->where('hostel_room_section_id', $allocation->hostel_room_section_id)
            ->when($allocation->exists, fn ($q) => $q->where('id', '!=', $allocation->id))
            ->exists();

        if ($sectionAlreadyOccupied) {
            throw ValidationException::withMessages([
                'hostel_room_section_id' => [__('hms.room_section_already_occupied')],
            ]);
        }
    }

    private function syncRoom(HostelRoomAllocation $allocation): void
    {
        $allocation->loadMissing('room');
        $allocation->room?->syncOccupancyFromAllocations();
    }
}
