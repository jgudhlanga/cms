<?php

namespace Database\Seeders\HMS;

use App\Models\HMS\HostelAmenity;
use App\Models\HMS\HostelRoomSection;
use Illuminate\Database\Seeder;

class RoomSectionAmenitySeeder extends Seeder
{
    public function run(): void
    {
        $amenityIds = HostelAmenity::query()
            ->orderBy('id')
            ->pluck('id')
            ->all();

        if ($amenityIds === []) {
            return;
        }

        HostelRoomSection::query()
            ->orderBy('id')
            ->chunkById(100, function ($sections) use ($amenityIds): void {
                foreach ($sections as $section) {
                    $section->amenities()->sync($amenityIds);
                }
            });
    }
}
