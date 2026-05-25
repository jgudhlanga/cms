<?php

namespace App\Services\HMS;

use App\Enums\HMS\HostelAllocationStatusEnum;
use App\Enums\HMS\HostelAllocationTypeEnum;
use App\Enums\HMS\HostelApplicationStatusEnum;
use App\Models\HMS\HmsSetting;
use App\Models\HMS\HostelApplication;
use App\Models\HMS\HostelRoom;
use App\Models\HMS\HostelRoomAllocation;
use App\Support\HMS\HostelApplicationPaymentVerification;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class HostelApplicationApprovalService
{
    public function __construct(
        protected HostelApplicationApprovalOptionsService $approvalOptionsService,
        protected HostelRoomAvailabilityService $roomAvailabilityService,
    ) {}

    public function approve(HostelApplication $application, int $hostelRoomId): void
    {
        DB::transaction(function () use ($application, $hostelRoomId): void {
            $application->loadMissing(['student']);

            $previousStatus = $application->getOriginal('status');
            $previousValue = $previousStatus instanceof HostelApplicationStatusEnum
                ? $previousStatus->value
                : (string) $previousStatus;

            $settings = HmsSetting::resolveForTenant($application->tenant_id);
            $allowedPreviousStatuses = [HostelApplicationStatusEnum::AWAITING_PAYMENT->value];

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

            $genderId = (int) ($application->gender_id ?? $application->student?->gender_id);
            $room = $this->resolveAssignableRoom($hostelRoomId, $genderId);

            if ($room === null) {
                throw ValidationException::withMessages([
                    'hostelRoomId' => [__('hms.room_at_capacity')],
                ]);
            }

            HostelRoomAllocation::query()->create([
                'tenant_id' => $application->tenant_id,
                'hostel_room_id' => $room->id,
                'student_id' => $application->student_id,
                'type' => HostelAllocationTypeEnum::DIRECT,
                'status' => HostelAllocationStatusEnum::ACTIVE,
                'check_in' => $application->check_in,
                'check_out' => $application->check_out,
            ]);
        });
    }

    private function resolveAssignableRoom(int $hostelRoomId, int $genderId): ?HostelRoom
    {
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
}
