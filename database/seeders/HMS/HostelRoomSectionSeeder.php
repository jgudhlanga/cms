<?php

namespace Database\Seeders\HMS;

use App\Models\HMS\HostelRoom;
use App\Services\HMS\HostelRoomSectionService;
use Illuminate\Database\Seeder;

class HostelRoomSectionSeeder extends Seeder
{
    public function run(): void
    {
        $sectionService = app(HostelRoomSectionService::class);

        HostelRoom::query()
            ->orderBy('id')
            ->chunkById(100, function ($rooms) use ($sectionService): void {
                foreach ($rooms as $room) {
                    $sectionService->ensureSectionsForRoom($room);
                }
            });
    }
}
