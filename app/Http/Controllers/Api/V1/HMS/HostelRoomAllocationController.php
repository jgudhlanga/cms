<?php

namespace App\Http\Controllers\Api\V1\HMS;

use App\Enums\HMS\HostelAllocationStatusEnum;
use App\Models\HMS\HostelRoomAllocation;
use App\Support\HMS\HmsStudentAccess;
use Illuminate\Http\Request;
use LaravelJsonApi\Core\Responses\MetaResponse;
use LaravelJsonApi\Laravel\Http\Controllers\JsonApiController;

class HostelRoomAllocationController extends JsonApiController
{
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
