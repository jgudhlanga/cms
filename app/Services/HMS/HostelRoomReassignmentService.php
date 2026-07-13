<?php

namespace App\Services\HMS;

use App\Models\HMS\Hostel;
use App\Models\HMS\HostelRoom;
use App\Models\HMS\HostelRoomAllocation;
use App\Models\HMS\HostelRoomSection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class HostelRoomReassignmentService
{
    public function __construct(
        protected HostelRoomAvailabilityService $roomAvailabilityService,
        protected HostelRoomSectionService $roomSectionService,
    ) {}

    /**
     * @return array{
     *     hostels: list<array{id: int, name: string, availableBeds: int, isFull: bool}>,
     * }
     */
    public function hostelsForAllocation(HostelRoomAllocation $allocation): array
    {
        $allocation->loadMissing(['student']);

        $genderId = (int) $allocation->student?->gender_id;

        return $this->roomAvailabilityService
            ->hostelsForGender($genderId)
            ->map(function (Hostel $hostel) use ($genderId, $allocation): array {
                $availableBeds = $this->availableBedsForHostel((int) $hostel->id, $allocation);

                return [
                    'id' => (int) $hostel->id,
                    'name' => (string) $hostel->name,
                    'availableBeds' => $availableBeds,
                    'isFull' => $availableBeds < 1,
                ];
            })
            ->values()
            ->all();
    }

    /**
     * @return list<array{id: int, name: string, maxOccupancy: int, currentOccupancy: int, availableBeds: int, occupancyLabel: string}>
     */
    public function roomsForHostel(HostelRoomAllocation $allocation, int $hostelId): array
    {
        $allocation->loadMissing(['student']);

        $genderId = (int) $allocation->student?->gender_id;
        $allowedHostelIds = $this->roomAvailabilityService
            ->hostelsForGender($genderId)
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->all();

        if (! in_array($hostelId, $allowedHostelIds, true)) {
            return [];
        }

        return HostelRoom::query()
            ->where('hostel_id', $hostelId)
            ->where('status', '!=', 'maintenance')
            ->withCount(['allocations as active_allocations_count' => fn ($query) => $query->active()])
            ->orderBy('name')
            ->get()
            ->filter(fn (HostelRoom $room) => $this->availableBedsForRoom($room, $allocation) > 0)
            ->map(function (HostelRoom $room) use ($allocation): array {
                $maxOccupancy = max(0, (int) $room->max_occupancy);
                $availableBeds = $this->availableBedsForRoom($room, $allocation);
                $currentOccupancy = max(0, $maxOccupancy - $availableBeds);

                return [
                    'id' => (int) $room->id,
                    'name' => (string) $room->name,
                    'maxOccupancy' => $maxOccupancy,
                    'currentOccupancy' => $currentOccupancy,
                    'availableBeds' => $availableBeds,
                    'occupancyLabel' => "{$currentOccupancy}/{$maxOccupancy}",
                ];
            })
            ->values()
            ->all();
    }

    public function reassign(HostelRoomAllocation $allocation, int $hostelRoomId): HostelRoomAllocation
    {
        return DB::transaction(function () use ($allocation, $hostelRoomId): HostelRoomAllocation {
            if (! $allocation->isActive()) {
                throw ValidationException::withMessages([
                    'hostelRoomId' => [__('hms.allocation_not_active')],
                ]);
            }

            $allocation->loadMissing(['student']);

            $genderId = (int) $allocation->student?->gender_id;
            $allowedHostelIds = $this->roomAvailabilityService
                ->hostelsForGender($genderId)
                ->pluck('id')
                ->map(fn ($id) => (int) $id)
                ->all();

            $room = HostelRoom::query()
                ->whereKey($hostelRoomId)
                ->whereIn('hostel_id', $allowedHostelIds)
                ->where('status', '!=', 'maintenance')
                ->withCount(['allocations as active_allocations_count' => fn ($query) => $query->active()])
                ->first();

            if ($room === null) {
                throw ValidationException::withMessages([
                    'hostelRoomId' => [__('hms.room_not_found')],
                ]);
            }

            if ($this->availableBedsForRoom($room, $allocation) < 1) {
                throw ValidationException::withMessages([
                    'hostelRoomId' => [__('hms.room_at_capacity')],
                ]);
            }

            $this->roomSectionService->ensureSectionsForRoom($room);
            $section = $this->resolveFreeSection($room, (int) $allocation->id);

            if ($section === null) {
                throw ValidationException::withMessages([
                    'hostelRoomId' => [__('hms.no_free_room_section')],
                ]);
            }

            $allocation->update([
                'hostel_room_id' => $room->id,
                'hostel_room_section_id' => $section->id,
            ]);

            return $allocation->fresh(['room.hostel', 'section']);
        });
    }

    private function availableBedsForHostel(int $hostelId, HostelRoomAllocation $allocation): int
    {
        return HostelRoom::query()
            ->where('hostel_id', $hostelId)
            ->where('status', '!=', 'maintenance')
            ->withCount(['allocations as active_allocations_count' => fn ($query) => $query->active()])
            ->get()
            ->sum(fn (HostelRoom $room) => $this->availableBedsForRoom($room, $allocation));
    }

    private function availableBedsForRoom(HostelRoom $room, HostelRoomAllocation $allocation): int
    {
        $max = max(0, (int) $room->max_occupancy);
        $occupied = HostelRoomAllocation::query()
            ->active()
            ->where('hostel_room_id', $room->id)
            ->when($allocation->exists, fn ($query) => $query->where('id', '!=', $allocation->id))
            ->count();

        $occupied = min(max(0, $occupied), $max);

        return max(0, $max - $occupied);
    }

    private function resolveFreeSection(HostelRoom $room, int $excludingAllocationId): ?HostelRoomSection
    {
        $occupiedSectionIds = HostelRoomAllocation::query()
            ->active()
            ->where('hostel_room_id', $room->id)
            ->where('id', '!=', $excludingAllocationId)
            ->whereNotNull('hostel_room_section_id')
            ->pluck('hostel_room_section_id')
            ->map(fn ($id) => (int) $id)
            ->all();

        return HostelRoomSection::query()
            ->where('hostel_room_id', $room->id)
            ->when($occupiedSectionIds !== [], fn ($query) => $query->whereNotIn('id', $occupiedSectionIds))
            ->orderBy('name')
            ->first();
    }
}
