<?php

namespace Database\Seeders\HMS;

use App\Enums\Shared\TenantEnum;
use App\Models\HMS\Hostel;
use App\Models\HMS\HostelRoom;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;

class HostelRoomsTableSeeder extends Seeder
{
    private const int GROUND_FLOOR_ROOMS = 16;

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
        $rows = [];

        foreach ($hostelConfig as $hostelName => $code) {
            $hostel = Hostel::query()
                ->where('tenant_id', $tenantId)
                ->where('name', $hostelName)
                ->first();

            if ($hostel === null) {
                continue;
            }

            for ($floor = 0; $floor < $hostel->floor_count; $floor++) {
                $roomCount = $floor === 0 ? self::GROUND_FLOOR_ROOMS : self::UPPER_FLOOR_ROOMS;

                for ($roomNumber = 1; $roomNumber <= $roomCount; $roomNumber++) {
                    $rows[] = [
                        'tenant_id' => $tenantId,
                        'hostel_id' => $hostel->id,
                        'name' => sprintf('%s-%d-%02d', $code, $floor, $roomNumber),
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
        }

        foreach (array_chunk($rows, 100) as $chunk) {
            HostelRoom::query()->upsert(
                $chunk,
                ['hostel_id', 'name'],
                ['room_type', 'capacity', 'max_occupancy', 'current_occupancy', 'status', 'floor_number', 'description', 'updated_at'],
            );
        }
    }
}
