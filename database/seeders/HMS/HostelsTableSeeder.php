<?php

namespace Database\Seeders\HMS;

use App\Enums\Shared\TenantEnum;
use App\Models\HMS\Hostel;
use Illuminate\Database\Seeder;

class HostelsTableSeeder extends Seeder
{
    public function run(): void
    {
        $tenantId = TenantEnum::HARARE_POLY->id();

        $hostels = [
            ['name' => 'Hostel A', 'floor_count' => 3],
            ['name' => 'Hostel B', 'floor_count' => 3],
            ['name' => 'Hostel C', 'floor_count' => 4],
            ['name' => 'Hostel D', 'floor_count' => 4],
            ['name' => 'Hostel E', 'floor_count' => 4],
            ['name' => 'Hostel F', 'floor_count' => 4],
        ];

        foreach ($hostels as $hostel) {
            $roomsCount = 18 + ($hostel['floor_count'] - 1) * 23;

            Hostel::query()->updateOrCreate(
                ['name' => $hostel['name']],
                [
                    'tenant_id' => $tenantId,
                    'floor_count' => $hostel['floor_count'],
                    'rooms_count' => $roomsCount,
                    'capacity' => $roomsCount * 2,
                    'status' => 'active',
                    'warden_id' => null,
                    'location' => null,
                    'type' => null,
                    'description' => null,
                ],
            );
        }
    }
}
