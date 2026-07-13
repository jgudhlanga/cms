<?php

namespace App\Http\Controllers\Api\V1\HMS;

use App\Enums\HMS\HostelAllocationStatusEnum;
use App\Models\HMS\HostelRoomAllocation;
use App\Services\HMS\HostelRoomReassignmentService;
use App\Support\HMS\HmsStudentAccess;
use Illuminate\Http\Request;
use LaravelJsonApi\Core\Responses\DataResponse;
use LaravelJsonApi\Core\Responses\MetaResponse;
use LaravelJsonApi\Laravel\Http\Controllers\JsonApiController;
use LaravelJsonApi\Laravel\Http\Requests\ResourceQuery;
use LaravelJsonApi\Laravel\Http\Requests\ResourceRequest;

class HostelRoomAllocationController extends JsonApiController
{
    public function __construct(
        protected HostelRoomReassignmentService $reassignmentService,
    ) {}

    public function updating(HostelRoomAllocation $hostelRoomAllocation, ResourceRequest $request, ResourceQuery $query): DataResponse
    {
        abort_unless($request->user()?->can('update:hostel-room-allocations', $hostelRoomAllocation), 403);

        $hostelRoomId = (int) data_get($request->validated(), 'hostelRoomId', 0);

        $updated = $this->reassignmentService->reassign($hostelRoomAllocation, $hostelRoomId);

        return DataResponse::make($updated)->withQueryParameters($query);
    }

    public function reassignmentOptions(HostelRoomAllocation $hostelRoomAllocation, Request $request): MetaResponse
    {
        abort_unless($request->user() !== null, 403);
        abort_unless($request->user()->can('update:hostel-room-allocations', $hostelRoomAllocation), 403);

        return MetaResponse::make([
            'hostels' => $this->reassignmentService->hostelsForAllocation($hostelRoomAllocation),
        ]);
    }

    public function reassignmentRooms(HostelRoomAllocation $hostelRoomAllocation, Request $request): MetaResponse
    {
        abort_unless($request->user() !== null, 403);
        abort_unless($request->user()->can('update:hostel-room-allocations', $hostelRoomAllocation), 403);

        $hostelId = $request->query('hostelId');

        if (! is_numeric($hostelId) || (int) $hostelId < 1) {
            return MetaResponse::make(['rooms' => []]);
        }

        return MetaResponse::make([
            'rooms' => $this->reassignmentService->roomsForHostel($hostelRoomAllocation, (int) $hostelId),
        ]);
    }

    public function roommates(HostelRoomAllocation $hostelRoomAllocation, Request $request): MetaResponse
    {
        abort_unless($request->user() !== null, 403);
        abort_unless(
            HmsStudentAccess::canViewAllocation($request->user(), $hostelRoomAllocation),
            403,
        );

        $hostelRoomAllocation->loadMissing([
            'student.user',
            'room.hostel',
        ]);

        $roommates = HostelRoomAllocation::query()
            ->with([
                'student.user',
                'student.latestEnrolment.departmentCourse.course',
                'student.latestEnrolment.departmentLevel.level',
            ])
            ->where('hostel_room_id', $hostelRoomAllocation->hostel_room_id)
            ->where('status', HostelAllocationStatusEnum::ACTIVE->value)
            ->when(
                $hostelRoomAllocation->student_id !== null,
                fn ($query) => $query->where('student_id', '!=', $hostelRoomAllocation->student_id),
            )
            ->get()
            ->map(fn (HostelRoomAllocation $allocation): array => [
                'id' => (int) $allocation->id,
                'studentId' => (int) $allocation->student_id,
                'name' => $allocation->student?->user?->full_name,
                'studentNumber' => $allocation->student?->student_number,
                'course' => $allocation->student?->latestEnrolment?->departmentCourse?->course?->name,
                'level' => $allocation->student?->latestEnrolment?->departmentLevel?->level?->name,
            ])
            ->values()
            ->all();

        return MetaResponse::make([
            'roommates' => $roommates,
        ]);
    }
}
