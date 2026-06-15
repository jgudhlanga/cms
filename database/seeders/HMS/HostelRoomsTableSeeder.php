<?php

namespace Database\Seeders\HMS;

use App\Enums\Shared\TenantEnum;
use App\Models\HMS\Hostel;
use App\Models\HMS\HostelRoom;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;

class HostelRoomsTableSeeder extends Seeder
{
    private const int GROUND_FLOOR_ROOMS = 18;

    private const int UPPER_FLOOR_ROOMS = 23;

    public function run(): void
    {
        $tenantId = TenantEnum::HARARE_POLY->id();

        $hostelConfig = [
            'Hostel A' => 'A',
            'Hostel B' => 'B',
            'Hostel C' => 'C',
            'Hostel D' => 'D',
            'Hostel E' => 'E',
            'Hostel F' => 'F',
        ];

        $now = Carbon::now();

        foreach ($hostelConfig as $hostelName => $code) {
            $hostel = Hostel::query()
                ->where('tenant_id', $tenantId)
                ->where('name', $hostelName)
                ->first();

            if ($hostel === null) {
                continue;
            }

            $rows = [];

            for ($floor = 0; $floor < $hostel->floor_count; $floor++) {
                $roomStart = $floor === 0 ? 2 : 1;
                $roomEnd = $floor === 0
                    ? $roomStart + self::GROUND_FLOOR_ROOMS - 1
                    : self::UPPER_FLOOR_ROOMS;

                for ($roomNumber = $roomStart; $roomNumber <= $roomEnd; $roomNumber++) {
                    $rows[] = [
                        'tenant_id' => $tenantId,
                        'hostel_id' => $hostel->id,
                        'name' => $this->buildRoomName($code, $floor, $roomNumber),
                        'room_type' => 'double',
                        'capacity' => 2,
                        'max_occupancy' => 2,
                        'current_occupancy' => 0,
                        'status' => 'vacant',
                        'floor_number' => $floor,
                        'description' => null,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ];
                }
            }

            if ($rows === []) {
                continue;
            }

            $existingNames = HostelRoom::query()
                ->where('hostel_id', $hostel->id)
                ->whereIn('name', array_column($rows, 'name'))
                ->pluck('name')
                ->flip();

            $newRows = array_values(array_filter(
                $rows,
                static fn (array $row): bool => ! $existingNames->has($row['name']),
            ));

            foreach (array_chunk($newRows, 100) as $chunk) {
                HostelRoom::query()->insert($chunk);
            }
        }

        $this->call(HostelAmenitySeeder::class);
    }

    private function buildRoomName(string $code, int $floor, int $roomNumber): string
    {
        return sprintf('%s%d%02d', $code, $floor, $roomNumber);
    }
}
