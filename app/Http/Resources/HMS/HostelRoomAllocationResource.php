<?php

namespace App\Http\Resources\HMS;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class HostelRoomAllocationResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $student = $this->resource->student;
        $enrolment = $student?->latestEnrolment;

        return [
            'type' => 'hostel_room_allocation',
            'id' => $this->resource->id,
            'attributes' => [
                'allocationType' => $this->resource->type?->value,
                'allocationTypeLabel' => $this->resource->type?->label(),
                'status' => $this->resource->status?->value,
                'statusLabel' => $this->resource->status?->label(),
                'checkIn' => $this->resource->check_in,
                'checkOut' => $this->resource->check_out,
                'studentId' => $student?->id,
                'studentNumber' => $student?->student_number,
                'studentName' => $student?->user?->full_name,
                'gender' => $student?->gender?->title,
                'course' => $enrolment?->departmentCourse?->course?->name,
                'level' => $enrolment?->departmentLevel?->level?->name,
                'hostelId' => $this->resource->room?->hostel_id,
                'hostelName' => $this->resource->room?->hostel?->name,
                'roomId' => $this->resource->hostel_room_id,
                'roomName' => $this->resource->room?->name,
                'createdAt' => $this->resource->created_at,
                'updatedAt' => $this->resource->updated_at,
                'deletedAt' => $this->resource->deleted_at,
            ],
        ];
    }
}
