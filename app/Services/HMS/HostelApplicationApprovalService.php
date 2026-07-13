<?php

namespace App\Services\HMS;

use App\Enums\HMS\HostelAllocationStatusEnum;
use App\Enums\HMS\HostelAllocationTypeEnum;
use App\Enums\HMS\HostelApplicationStatusEnum;
use App\Models\HMS\HmsSetting;
use App\Models\HMS\HostelApplication;
use App\Models\HMS\HostelRoom;
use App\Models\HMS\HostelRoomAllocation;
use App\Models\HMS\HostelRoomSection;
use App\Support\HMS\HostelApplicationPaymentVerification;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class HostelApplicationApprovalService
{
    public function __construct(
        protected HostelApplicationApprovalOptionsService $approvalOptionsService,
        protected HostelRoomAvailabilityService $roomAvailabilityService,
        protected HostelRoomSectionService $roomSectionService,
    ) {}

    /**
     * @return array{
     *     hostelId: int,
     *     hostelName: string,
     *     roomId: int,
     *     roomName: string,
     *     sectionId: int,
     *     sectionName: string,
     *     floorNumber: ?string,
     * }|null
     */
    public function previewAllocation(HostelApplication $application, ?int $hostelRoomId = null): ?array
    {
        $application->loadMissing(['student']);

        $settings = HmsSetting::resolveForTenant($application->tenant_id);

        $room = ($hostelRoomId ?? 0) > 0
            ? $this->resolveManualRoom($application, $hostelRoomId)
            : ($settings->auto_allocate_rooms
                ? $this->resolveAutomaticRoom($application)
                : null);

        if ($room === null) {
            return null;
        }

        $this->roomSectionService->ensureSectionsForRoom($room);
        $section = $this->resolveFreeSection($room);

        if ($section === null) {
            return null;
        }

        $room->loadMissing('hostel');

        return [
            'hostelId' => (int) $room->hostel_id,
            'hostelName' => (string) $room->hostel->name,
            'roomId' => (int) $room->id,
            'roomName' => (string) $room->name,
            'sectionId' => (int) $section->id,
            'sectionName' => (string) $section->name,
            'floorNumber' => filled($room->floor_number) ? (string) $room->floor_number : null,
        ];
    }

    public function approve(HostelApplication $application, ?int $hostelRoomId = null): void
    {
        DB::transaction(function () use ($application, $hostelRoomId): void {
            $application->loadMissing(['student']);

            $previousStatus = $application->getOriginal('status');
            $previousValue = $previousStatus instanceof HostelApplicationStatusEnum
                ? $previousStatus->value
                : (string) $previousStatus;

            $settings = HmsSetting::resolveForTenant($application->tenant_id);
            $allowedPreviousStatuses = [
                HostelApplicationStatusEnum::AWAITING_PAYMENT->value,
                HostelApplicationStatusEnum::PAID->value,
            ];

            if (HostelApplicationPaymentVerification::allowsDirectRoomAllocation($settings)) {
                $allowedPreviousStatuses[] = HostelApplicationStatusEnum::PENDING->value;
            }

            if (! in_array($previousValue, $allowedPreviousStatuses, true)) {
                throw ValidationException::withMessages([
                    'status' => [__('hms.application_cannot_be_approved')],
                ]);
            }

            $blockers = $this->approvalOptionsService->blockersForApplication($application, $settings);

            if ($blockers !== []) {
                throw ValidationException::withMessages([
                    'status' => [__('hms.application_cannot_be_approved')],
                ]);
            }

            $room = ($hostelRoomId ?? 0) > 0
                ? $this->resolveManualRoom($application, $hostelRoomId)
                : ($settings->auto_allocate_rooms
                    ? $this->resolveAutomaticRoom($application)
                    : $this->resolveManualRoom($application, $hostelRoomId));

            if ($room === null) {
                throw ValidationException::withMessages([
                    'hostelRoomId' => [__('hms.no_hostel_capacity')],
                ]);
            }

            $this->roomSectionService->ensureSectionsForRoom($room);
            $section = $this->resolveFreeSection($room);

            if ($section === null) {
                throw ValidationException::withMessages([
                    'hostel_room_section_id' => [__('hms.no_free_room_section')],
                ]);
            }

            HostelRoomAllocation::query()->create([
                'tenant_id' => $application->tenant_id,
                'hostel_room_id' => $room->id,
                'hostel_room_section_id' => $section->id,
                'student_id' => $application->student_id,
                'type' => HostelAllocationTypeEnum::DIRECT,
                'status' => HostelAllocationStatusEnum::ACTIVE,
                'check_in' => $application->check_in,
                'check_out' => $application->check_out,
            ]);
        });
    }

    private function resolveFreeSection(HostelRoom $room): ?HostelRoomSection
    {
        $occupiedSectionIds = HostelRoomAllocation::query()
            ->active()
            ->where('hostel_room_id', $room->id)
            ->whereNotNull('hostel_room_section_id')
            ->pluck('hostel_room_section_id')
            ->map(fn ($id) => (int) $id)
            ->all();

        return HostelRoomSection::query()
            ->where('hostel_room_id', $room->id)
            ->when($occupiedSectionIds !== [], fn ($query) => $query->whereNotIn('id', $occupiedSectionIds))
            ->orderBy('name')
            ->first();
    }

    private function resolveManualRoom(HostelApplication $application, ?int $hostelRoomId): ?HostelRoom
    {
        if (($hostelRoomId ?? 0) < 1) {
            return null;
        }

        $genderId = (int) ($application->gender_id ?? $application->student?->gender_id);

        $allowedHostelIds = $this->roomAvailabilityService
            ->hostelsForGender($genderId)
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->all();

        $room = HostelRoom::query()
            ->whereKey($hostelRoomId)
            ->whereIn('hostel_id', $allowedHostelIds)
            ->where('status', '!=', 'maintenance')
            ->withCount(['allocations as active_allocations_count' => fn ($query) => $query->active()])
            ->first();

        if ($room === null) {
            return null;
        }

        if ($this->roomAvailabilityService->availableBedsForRoom($room) < 1) {
            return null;
        }

        return $room;
    }

    private function resolveAutomaticRoom(HostelApplication $application): ?HostelRoom
    {
        $genderId = (int) ($application->gender_id ?? $application->student?->gender_id);
        $hostels = $this->roomAvailabilityService
            ->hostelsForGender($genderId)
            ->sortBy('name')
            ->values();

        $rooms = $hostels
            ->flatMap(fn ($hostel) => $this->roomAvailabilityService->availableRoomsForHostel((int) $hostel->id))
            ->filter(fn (HostelRoom $room) => $this->roomAvailabilityService->availableBedsForRoom($room) > 0)
            ->values();

        if ($rooms->isEmpty()) {
            return null;
        }

        $groundFloorRooms = $rooms
            ->filter(fn (HostelRoom $room) => (int) ($room->floor_number ?? 0) === 0)
            ->sort(function (HostelRoom $left, HostelRoom $right): int {
                return [(int) $left->hostel_id, (string) $left->name]
                    <=> [(int) $right->hostel_id, (string) $right->name];
            })
            ->values();

        $upperFloorRooms = $rooms
            ->filter(fn (HostelRoom $room) => (int) ($room->floor_number ?? 0) > 0)
            ->sort(function (HostelRoom $left, HostelRoom $right): int {
                return [
                    (int) ($left->floor_number ?? 0),
                    (int) $left->hostel_id,
                    (string) $left->name,
                ] <=> [
                    (int) ($right->floor_number ?? 0),
                    (int) $right->hostel_id,
                    (string) $right->name,
                ];
            })
            ->values();

        $orderedRooms = $groundFloorRooms->concat($upperFloorRooms);

        return $orderedRooms->first();
    }
}
