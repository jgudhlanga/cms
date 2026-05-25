<?php

namespace App\Services\HMS;

use App\Models\HMS\Hostel;
use App\Models\HMS\HostelRoom;
use App\Models\Shared\Gender;
use Illuminate\Support\Collection;

class HostelRoomAvailabilityService
{
    public const BLOCKER_NO_HOSTEL_CAPACITY = 'no_hostel_capacity';

    public const BLOCKER_UNKNOWN_GENDER = 'unknown_gender_for_hostel';

    /** @var list<string> */
    private const FEMALE_HOSTEL_NAMES = ['Hostel A', 'Hostel B', 'Hostel C'];

    /** @var list<string> */
    private const MALE_HOSTEL_NAMES = ['Hostel D', 'Hostel E', 'Hostel F'];

    public function hasCapacityForGender(?int $genderId): bool
    {
        return $this->summaryForGender($genderId)['availableBeds'] > 0;
    }

    /**
     * @return array{
     *     availableBeds: int,
     *     hostels: list<string>,
     *     roomCount: int,
     *     blocker: string|null,
     * }
     */
    public function summaryForGender(?int $genderId): array
    {
        $hostelNames = $this->hostelNamesForGender($genderId);

        if ($hostelNames === null) {
            return [
                'availableBeds' => 0,
                'hostels' => [],
                'roomCount' => 0,
                'blocker' => self::BLOCKER_UNKNOWN_GENDER,
            ];
        }

        $rooms = $this->roomsForHostels($hostelNames);
        $availableBeds = $this->countAvailableBeds($rooms);

        return [
            'availableBeds' => $availableBeds,
            'hostels' => $hostelNames,
            'roomCount' => $rooms->count(),
            'blocker' => $availableBeds > 0 ? null : self::BLOCKER_NO_HOSTEL_CAPACITY,
        ];
    }

    /**
     * @return list<string>|null
     */
    public function hostelNamesForGender(?int $genderId): ?array
    {
        if ($genderId === null) {
            return null;
        }

        $title = strtolower(trim((string) Gender::query()->whereKey($genderId)->value('title')));

        if ($title === '') {
            return null;
        }

        if (str_contains($title, 'female') || str_contains($title, 'woman')) {
            return self::FEMALE_HOSTEL_NAMES;
        }

        if (str_contains($title, 'male') && ! str_contains($title, 'female')) {
            return self::MALE_HOSTEL_NAMES;
        }

        return null;
    }

    /**
     * @return 'female'|'male'|null
     */
    public function hostelTypeForGender(?int $genderId): ?string
    {
        $hostelNames = $this->hostelNamesForGender($genderId);

        if ($hostelNames === self::FEMALE_HOSTEL_NAMES) {
            return 'female';
        }

        if ($hostelNames === self::MALE_HOSTEL_NAMES) {
            return 'male';
        }

        return null;
    }

    /**
     * @return Collection<int, Hostel>
     */
    public function hostelsForGender(?int $genderId): Collection
    {
        $hostelNames = $this->hostelNamesForGender($genderId);
        $hostelType = $this->hostelTypeForGender($genderId);

        if ($hostelNames === null) {
            return collect();
        }

        return Hostel::query()
            ->where(function ($query) use ($hostelNames, $hostelType): void {
                if ($hostelType !== null) {
                    $query->where('type', $hostelType);
                }

                $query->orWhereIn('name', $hostelNames);
            })
            ->orderBy('name')
            ->get();
    }

    /**
     * @param  list<string>  $hostelNames
     * @return Collection<int, HostelRoom>
     */
    public function availableRoomsForHostel(int $hostelId): Collection
    {
        return HostelRoom::query()
            ->where('hostel_id', $hostelId)
            ->where('status', '!=', 'maintenance')
            ->withCount(['allocations as active_allocations_count' => fn ($query) => $query->active()])
            ->get()
            ->filter(fn (HostelRoom $room) => $this->availableBedsForRoom($room) > 0)
            ->values();
    }

    public function availableBedsForRoom(HostelRoom $room): int
    {
        $max = max(0, (int) $room->max_occupancy);
        $occupied = min(max(0, (int) ($room->active_allocations_count ?? $room->allocations()->active()->count())), $max);

        return max(0, $max - $occupied);
    }

    /**
     * @param  list<string>  $hostelNames
     * @return Collection<int, HostelRoom>
     */
    private function roomsForHostels(array $hostelNames): Collection
    {
        $hostelType = $hostelNames === self::FEMALE_HOSTEL_NAMES
            ? 'female'
            : ($hostelNames === self::MALE_HOSTEL_NAMES ? 'male' : null);

        return HostelRoom::query()
            ->whereHas('hostel', function ($query) use ($hostelNames, $hostelType): void {
                $query->where(function ($inner) use ($hostelNames, $hostelType): void {
                    if ($hostelType !== null) {
                        $inner->where('type', $hostelType);
                    }

                    $inner->orWhereIn('name', $hostelNames);
                });
            })
            ->where('status', '!=', 'maintenance')
            ->withCount(['allocations as active_allocations_count' => fn ($query) => $query->active()])
            ->get();
    }

    /**
     * @param  Collection<int, HostelRoom>  $rooms
     */
    private function countAvailableBeds(Collection $rooms): int
    {
        $total = 0;

        foreach ($rooms as $room) {
            $max = max(0, (int) $room->max_occupancy);
            $occupied = min(max(0, (int) $room->active_allocations_count), $max);
            $total += max(0, $max - $occupied);
        }

        return $total;
    }
}
