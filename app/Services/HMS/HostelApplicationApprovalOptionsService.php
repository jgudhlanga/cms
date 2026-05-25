<?php

namespace App\Services\HMS;

use App\Enums\HMS\HostelApplicationStatusEnum;
use App\Enums\HMS\HostelApplicationTypeEnum;
use App\Models\HMS\HmsSetting;
use App\Models\HMS\Hostel;
use App\Models\HMS\HostelApplication;
use App\Models\HMS\HostelRoom;
use App\Support\HMS\HostelApplicationPaymentVerification;

class HostelApplicationApprovalOptionsService
{
    public const BLOCKER_NOT_AWAITING_PAYMENT = 'not_awaiting_payment';

    public const BLOCKER_GUEST_NOT_ALLOCATABLE = 'guest_not_allocatable';

    public const BLOCKER_UNKNOWN_GENDER = 'unknown_gender_for_hostel';

    public const BLOCKER_NO_HOSTEL_CAPACITY = 'no_hostel_capacity';

    public const BLOCKER_STUDENT_ALREADY_ALLOCATED = HostelStudentAllocationService::BLOCKER_STUDENT_ALREADY_ALLOCATED;

    public function __construct(
        protected HostelRoomAvailabilityService $roomAvailabilityService,
        protected HostelStudentAllocationService $allocationService,
    ) {}

    /**
     * @return array{
     *     canApprove: bool,
     *     blockers: list<string>,
     *     hostels: list<array{id: int, name: string, availableBeds: int, isFull: bool}>,
     *     rooms: list<array{id: int, name: string, maxOccupancy: int, currentOccupancy: int, availableBeds: int, occupancyLabel: string}>,
     *     requiredPaymentVerification: list<string>,
     *     allowsDirectAllocation: bool,
     * }
     */
    public function forApplication(HostelApplication $application, ?int $hostelId = null): array
    {
        $settings = HmsSetting::resolveForTenant($application->tenant_id);
        $requiredPaymentVerification = HostelApplicationPaymentVerification::requiredApiKeys($settings);
        $allowsDirectAllocation = HostelApplicationPaymentVerification::allowsDirectRoomAllocation($settings);
        $blockers = $this->blockersForApplication($application, $settings);

        if ($blockers !== []) {
            return [
                'canApprove' => false,
                'blockers' => $blockers,
                'hostels' => [],
                'rooms' => [],
                'requiredPaymentVerification' => $requiredPaymentVerification,
                'allowsDirectAllocation' => $allowsDirectAllocation,
            ];
        }

        $genderId = (int) ($application->gender_id ?? $application->student?->gender_id);
        $hostels = $this->hostelsForGender($genderId);
        $rooms = $hostelId !== null
            ? $this->roomsForHostel($hostelId, $genderId)
            : [];

        $canApprove = collect($hostels)->contains(fn (array $hostel) => ! $hostel['isFull']);

        if (! $canApprove) {
            $blockers[] = self::BLOCKER_NO_HOSTEL_CAPACITY;
        }

        return [
            'canApprove' => $canApprove,
            'blockers' => array_values(array_unique($blockers)),
            'hostels' => $hostels,
            'rooms' => $rooms,
            'requiredPaymentVerification' => $requiredPaymentVerification,
            'allowsDirectAllocation' => $allowsDirectAllocation,
        ];
    }

    /**
     * @return list<string>
     */
    public function blockersForApplication(
        HostelApplication $application,
        ?HmsSetting $settings = null,
    ): array {
        $blockers = [];
        $status = $this->effectiveStatusForReview($application);

        if ($status !== HostelApplicationStatusEnum::AWAITING_PAYMENT) {
            $settings ??= HmsSetting::resolveForTenant($application->tenant_id);
            $canAllocateFromPending = $status === HostelApplicationStatusEnum::PENDING
                && HostelApplicationPaymentVerification::allowsDirectRoomAllocation($settings);

            if (! $canAllocateFromPending) {
                $blockers[] = self::BLOCKER_NOT_AWAITING_PAYMENT;
            }
        }

        if ($application->type === HostelApplicationTypeEnum::GUEST) {
            $blockers[] = self::BLOCKER_GUEST_NOT_ALLOCATABLE;
        }

        if (blank($application->student_id)) {
            $blockers[] = self::BLOCKER_GUEST_NOT_ALLOCATABLE;
        }

        $genderId = $application->gender_id ?? $application->student?->gender_id;

        if ($this->roomAvailabilityService->hostelNamesForGender($genderId) === null) {
            $blockers[] = self::BLOCKER_UNKNOWN_GENDER;
        }

        if ($application->student_id !== null
            && $this->allocationService->studentHasOpenAllocation((int) $application->student_id)) {
            $blockers[] = self::BLOCKER_STUDENT_ALREADY_ALLOCATED;
        }

        return $blockers;
    }

    private function effectiveStatusForReview(HostelApplication $application): HostelApplicationStatusEnum
    {
        if (! $application->isDirty('status')) {
            return $application->status;
        }

        $original = $application->getOriginal('status');

        if ($original instanceof HostelApplicationStatusEnum) {
            return $original;
        }

        return HostelApplicationStatusEnum::tryFrom((string) $original) ?? $application->status;
    }

    /**
     * @return list<array{id: int, name: string, availableBeds: int, isFull: bool}>
     */
    private function hostelsForGender(int $genderId): array
    {
        return $this->roomAvailabilityService
            ->hostelsForGender($genderId)
            ->map(function (Hostel $hostel) use ($genderId): array {
                $availableBeds = $this->availableBedsForHostel((int) $hostel->id, $genderId);

                return [
                    'id' => (int) $hostel->id,
                    'name' => (string) $hostel->name,
                    'availableBeds' => $availableBeds,
                    'isFull' => $availableBeds === 0,
                ];
            })
            ->values()
            ->all();
    }

    private function availableBedsForHostel(int $hostelId, int $genderId): int
    {
        $allowedHostelIds = $this->roomAvailabilityService
            ->hostelsForGender($genderId)
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->all();

        if (! in_array($hostelId, $allowedHostelIds, true)) {
            return 0;
        }

        return $this->roomAvailabilityService
            ->availableRoomsForHostel($hostelId)
            ->sum(fn (HostelRoom $room) => $this->roomAvailabilityService->availableBedsForRoom($room));
    }

    /**
     * @return list<array{id: int, name: string, maxOccupancy: int, currentOccupancy: int, availableBeds: int, occupancyLabel: string}>
     */
    public function roomsForApplication(HostelApplication $application, int $hostelId): array
    {
        $genderId = (int) ($application->gender_id ?? $application->student?->gender_id);

        return $this->roomsForHostel($hostelId, $genderId);
    }

    /**
     * @return list<array{id: int, name: string, maxOccupancy: int, currentOccupancy: int, availableBeds: int, occupancyLabel: string}>
     */
    private function roomsForHostel(int $hostelId, int $genderId): array
    {
        $allowedHostelIds = $this->roomAvailabilityService
            ->hostelsForGender($genderId)
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->all();

        if (! in_array($hostelId, $allowedHostelIds, true)) {
            return [];
        }

        return $this->roomAvailabilityService
            ->availableRoomsForHostel($hostelId)
            ->map(function (HostelRoom $room): array {
                $max = max(0, (int) $room->max_occupancy);
                $occupied = min(max(0, (int) $room->active_allocations_count), $max);
                $availableBeds = max(0, $max - $occupied);

                return [
                    'id' => (int) $room->id,
                    'name' => (string) $room->name,
                    'maxOccupancy' => $max,
                    'currentOccupancy' => $occupied,
                    'availableBeds' => $availableBeds,
                    'occupancyLabel' => sprintf('%d/%d', $occupied, $max),
                ];
            })
            ->values()
            ->all();
    }
}
