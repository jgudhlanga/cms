<?php

namespace App\Services\HMS;

use App\Enums\HMS\HostelNoticeStatusEnum;
use App\Enums\HMS\HostelNoticeTypeEnum;
use App\Models\HMS\HostelNotice;
use App\Models\HMS\HostelNoticeFloor;
use App\Models\Students\Student;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;

class HostelNoticeAudienceService
{
    /**
     * @param  list<int>  $hostelIds
     * @param  list<array{hostelId: int, floorNumber: int}>  $floors
     * @param  list<int>  $studentIds
     */
    public function syncAudience(HostelNotice $notice, array $hostelIds, array $floors, array $studentIds): void
    {
        $notice->hostels()->sync($hostelIds);

        $notice->noticeFloors()->delete();
        foreach ($floors as $floor) {
            $hostelId = (int) ($floor['hostelId'] ?? $floor['hostel_id'] ?? 0);
            $floorNumber = (int) ($floor['floorNumber'] ?? $floor['floor_number'] ?? -1);

            if ($hostelId > 0 && $floorNumber >= 0) {
                HostelNoticeFloor::query()->create([
                    'hostel_notice_id' => $notice->id,
                    'hostel_id' => $hostelId,
                    'floor_number' => $floorNumber,
                ]);
            }
        }

        $notice->students()->sync($studentIds);
    }

    /**
     * @return array{
     *     hostelIds: list<int>,
     *     floors: list<array{hostelId: int, floorNumber: int}>,
     *     studentIds: list<int>
     * }
     */
    public function audiencePayload(HostelNotice $notice): array
    {
        $notice->loadMissing(['hostels', 'noticeFloors', 'students']);

        return [
            'hostelIds' => $notice->hostels->pluck('id')->map(fn ($id) => (int) $id)->values()->all(),
            'floors' => $notice->noticeFloors
                ->map(fn (HostelNoticeFloor $floor): array => [
                    'hostelId' => (int) $floor->hostel_id,
                    'floorNumber' => (int) $floor->floor_number,
                ])
                ->values()
                ->all(),
            'studentIds' => $notice->students->pluck('id')->map(fn ($id) => (int) $id)->values()->all(),
        ];
    }

    public function publishedForStudent(Student $student): Builder
    {
        $student->loadMissing([
            'activeHostelAllocation.room',
        ]);

        $allocation = $student->activeHostelAllocation;
        $hostelId = $allocation?->room?->hostel_id;
        $floorNumber = $allocation?->room?->floor_number;
        $now = Carbon::now();

        return HostelNotice::query()
            ->where('status', HostelNoticeStatusEnum::PUBLISHED->value)
            ->where(function (Builder $query) use ($now): void {
                $query->whereNull('expires_at')->orWhere('expires_at', '>', $now);
            })
            ->where(function (Builder $query) use ($student, $hostelId, $floorNumber): void {
                $query->whereDoesntHave('hostels')
                    ->whereDoesntHave('noticeFloors')
                    ->whereDoesntHave('students');

                $query->orWhereHas('students', fn (Builder $studentQuery) => $studentQuery->where('students.id', $student->id));

                if ($hostelId !== null) {
                    $query->orWhereHas('hostels', fn (Builder $hostelQuery) => $hostelQuery->where('hostels.id', $hostelId));

                    if ($floorNumber !== null) {
                        $query->orWhereHas('noticeFloors', function (Builder $floorQuery) use ($hostelId, $floorNumber): void {
                            $floorQuery
                                ->where('hostel_id', $hostelId)
                                ->where('floor_number', $floorNumber);
                        });
                    }
                }
            });
    }

    public function applyPublishingRules(HostelNotice $notice): void
    {
        if ($notice->is_urgent && $notice->type !== HostelNoticeTypeEnum::URGENT) {
            $notice->type = HostelNoticeTypeEnum::URGENT;
        }

        if ($notice->status === HostelNoticeStatusEnum::PUBLISHED && $notice->published_at === null) {
            $notice->published_at = Carbon::now();
        }

        if ($notice->expires_at !== null && $notice->expires_at->isPast()) {
            $notice->status = HostelNoticeStatusEnum::EXPIRED;
        }
    }
}
