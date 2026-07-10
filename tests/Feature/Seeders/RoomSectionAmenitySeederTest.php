<?php

use App\Enums\Shared\TenantEnum;
use App\Models\HMS\Hostel;
use App\Models\HMS\HostelAmenity;
use App\Models\HMS\HostelRoom;
use App\Models\HMS\HostelRoomSection;
use App\Services\HMS\HostelRoomSectionService;
use Database\Seeders\HMS\RoomSectionAmenitySeeder;

it('links every amenity to every room section', function (): void {
    $tenantId = TenantEnum::HARARE_POLY->id();

    $amenityOne = HostelAmenity::query()->create([
        'tenant_id' => $tenantId,
        'name' => 'Chair',
        'slug' => 'chair-seeder-test',
    ]);

    $amenityTwo = HostelAmenity::query()->create([
        'tenant_id' => $tenantId,
        'name' => 'Desk',
        'slug' => 'desk-seeder-test',
    ]);

    $hostel = Hostel::query()->create([
        'tenant_id' => $tenantId,
        'name' => 'Amenity Seeder Hostel',
        'location' => 'North',
        'floor_count' => 2,
        'rooms_count' => 1,
        'capacity' => 2,
        'status' => 'active',
        'type' => 'male',
    ]);

    $room = HostelRoom::query()->create([
        'tenant_id' => $tenantId,
        'hostel_id' => $hostel->id,
        'name' => 'AS-01',
        'room_type' => 'double',
        'capacity' => 2,
        'max_occupancy' => 2,
        'current_occupancy' => 0,
        'status' => 'vacant',
        'floor_number' => 1,
    ]);

    app(HostelRoomSectionService::class)->ensureSectionsForRoom($room);

    $sections = HostelRoomSection::query()
        ->where('hostel_room_id', $room->id)
        ->get();

    expect($sections)->not->toBeEmpty();

    $this->seed(RoomSectionAmenitySeeder::class);

    foreach ($sections as $section) {
        expect($section->amenities()->pluck('hostel_amenities.id')->all())
            ->toEqualCanonicalizing([$amenityOne->id, $amenityTwo->id]);
    }
});

it('is idempotent when room section amenities are re-seeded', function (): void {
    $tenantId = TenantEnum::HARARE_POLY->id();

    HostelAmenity::query()->create([
        'tenant_id' => $tenantId,
        'name' => 'Lamp',
        'slug' => 'lamp-seeder-test',
    ]);

    $hostel = Hostel::query()->create([
        'tenant_id' => $tenantId,
        'name' => 'Amenity Seeder Hostel Two',
        'location' => 'South',
        'floor_count' => 1,
        'rooms_count' => 1,
        'capacity' => 1,
        'status' => 'active',
        'type' => 'female',
    ]);

    $room = HostelRoom::query()->create([
        'tenant_id' => $tenantId,
        'hostel_id' => $hostel->id,
        'name' => 'AS-02',
        'room_type' => 'single',
        'capacity' => 1,
        'max_occupancy' => 1,
        'current_occupancy' => 0,
        'status' => 'vacant',
        'floor_number' => 1,
    ]);

    app(HostelRoomSectionService::class)->ensureSectionsForRoom($room);

    $this->seed(RoomSectionAmenitySeeder::class);
    $this->seed(RoomSectionAmenitySeeder::class);

    $section = HostelRoomSection::query()
        ->where('hostel_room_id', $room->id)
        ->firstOrFail();

    expect($section->amenities()->count())->toBe(HostelAmenity::query()->count());
});
