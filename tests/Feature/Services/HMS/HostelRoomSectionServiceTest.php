<?php

use App\Enums\Shared\TenantEnum;
use App\Models\HMS\Hostel;
use App\Models\HMS\HostelRoom;
use App\Models\HMS\HostelRoomSection;
use App\Services\HMS\HostelRoomSectionService;
use Database\Seeders\HMS\HostelRoomSectionSeeder;

it('creates default sections for a double room', function (): void {
    $tenantId = TenantEnum::HARARE_POLY->id();

    $hostel = Hostel::query()->create([
        'tenant_id' => $tenantId,
        'name' => 'Section Hostel',
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
        'name' => 'S-01',
        'room_type' => 'double',
        'capacity' => 2,
        'max_occupancy' => 2,
        'current_occupancy' => 0,
        'status' => 'vacant',
        'floor_number' => 1,
    ]);

    $sections = app(HostelRoomSectionService::class)->ensureSectionsForRoom($room);

    expect($sections->pluck('name')->all())->toBe(['A', 'B']);
});

it('re-syncs sections when the room type changes', function (): void {
    $tenantId = TenantEnum::HARARE_POLY->id();

    $hostel = Hostel::query()->create([
        'tenant_id' => $tenantId,
        'name' => 'Section Hostel Two',
        'location' => 'North',
        'floor_count' => 2,
        'rooms_count' => 1,
        'capacity' => 3,
        'status' => 'active',
        'type' => 'male',
    ]);

    $room = HostelRoom::query()->create([
        'tenant_id' => $tenantId,
        'hostel_id' => $hostel->id,
        'name' => 'S-02',
        'room_type' => 'triple',
        'capacity' => 3,
        'max_occupancy' => 3,
        'current_occupancy' => 0,
        'status' => 'vacant',
        'floor_number' => 2,
    ]);

    $service = app(HostelRoomSectionService::class);
    $service->ensureSectionsForRoom($room);

    $room->update(['room_type' => 'single']);

    $sections = $service->ensureSectionsForRoom($room->fresh());

    expect($sections->pluck('name')->all())->toBe(['A']);
});

it('seeds sections for existing rooms and remains idempotent', function (): void {
    $tenantId = TenantEnum::HARARE_POLY->id();

    $hostel = Hostel::query()->create([
        'tenant_id' => $tenantId,
        'name' => 'Section Seed Hostel',
        'location' => 'North',
        'floor_count' => 2,
        'rooms_count' => 2,
        'capacity' => 3,
        'status' => 'active',
        'type' => 'male',
    ]);

    $single = HostelRoom::query()->create([
        'tenant_id' => $tenantId,
        'hostel_id' => $hostel->id,
        'name' => 'Seed-S',
        'room_type' => 'single',
        'capacity' => 1,
        'max_occupancy' => 1,
        'current_occupancy' => 0,
        'status' => 'vacant',
        'floor_number' => 1,
    ]);

    $double = HostelRoom::query()->create([
        'tenant_id' => $tenantId,
        'hostel_id' => $hostel->id,
        'name' => 'Seed-D',
        'room_type' => 'double',
        'capacity' => 2,
        'max_occupancy' => 2,
        'current_occupancy' => 0,
        'status' => 'vacant',
        'floor_number' => 1,
    ]);

    $this->seed(HostelRoomSectionSeeder::class);
    $this->seed(HostelRoomSectionSeeder::class);

    expect(
        HostelRoomSection::query()
            ->where('hostel_room_id', $single->id)
            ->orderBy('name')
            ->pluck('name')
            ->all(),
    )->toBe(['A']);

    expect(
        HostelRoomSection::query()
            ->where('hostel_room_id', $double->id)
            ->orderBy('name')
            ->pluck('name')
            ->all(),
    )->toBe(['A', 'B']);

    expect(
        HostelRoomSection::query()
            ->whereIn('hostel_room_id', [$single->id, $double->id])
            ->count(),
    )->toBe(3);
});
