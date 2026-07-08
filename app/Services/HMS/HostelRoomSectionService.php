<?php

namespace App\Services\HMS;

use App\Models\HMS\HostelRoom;
use App\Models\HMS\HostelRoomSection;
use Illuminate\Support\Collection;

class HostelRoomSectionService
{
    /**
     * @return Collection<int, HostelRoomSection>
     */
    public function ensureSectionsForRoom(HostelRoom $room): Collection
    {
        $names = $this->defaultSectionNames($room->room_type);

        if ($names === []) {
            return collect();
        }

        foreach ($names as $name) {
            HostelRoomSection::query()->firstOrCreate(
                [
                    'hostel_room_id' => $room->id,
                    'name' => $name,
                ],
                [
                    'tenant_id' => $room->tenant_id,
                ],
            );
        }

        HostelRoomSection::query()
            ->where('hostel_room_id', $room->id)
            ->whereNotIn('name', $names)
            ->get()
            ->each(function (HostelRoomSection $section): void {
                $section->amenities()->detach();
                $section->delete();
            });

        return HostelRoomSection::query()
            ->where('hostel_room_id', $room->id)
            ->orderBy('name')
            ->get();
    }

    /**
     * @return list<string>
     */
    public function defaultSectionNames(string $roomType): array
    {
        return match ($roomType) {
            'single' => ['A'],
            'double' => ['A', 'B'],
            'triple' => ['A', 'B', 'C'],
            'suite' => ['A', 'B'],
            default => ['A'],
        };
    }
}
