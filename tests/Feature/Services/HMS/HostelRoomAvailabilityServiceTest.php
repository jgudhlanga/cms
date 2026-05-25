<?php

use App\Models\Shared\Gender;
use App\Services\HMS\HostelRoomAvailabilityService;
use Illuminate\Support\Str;

it('counts available beds in male hostel blocks', function (): void {
    ensureHostelRoomWithCapacity('Hostel D', 'D-UNIT-'.Str::random(4));

    $genderId = (int) Gender::query()->firstOrCreate(['title' => 'Male'])->id;

    $summary = app(HostelRoomAvailabilityService::class)->summaryForGender($genderId);

    expect($summary['blocker'])->toBeNull()
        ->and($summary['availableBeds'])->toBeGreaterThan(0)
        ->and($summary['hostels'])->toContain('Hostel D');
});

it('returns unknown gender blocker when gender cannot be mapped', function (): void {
    $genderId = (int) Gender::query()->firstOrCreate(['title' => 'Non-binary'])->id;

    $summary = app(HostelRoomAvailabilityService::class)->summaryForGender($genderId);

    expect($summary['blocker'])->toBe(HostelRoomAvailabilityService::BLOCKER_UNKNOWN_GENDER)
        ->and($summary['availableBeds'])->toBe(0);
});
