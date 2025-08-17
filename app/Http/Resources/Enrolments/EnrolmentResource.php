<?php

namespace App\Http\Resources\Enrolments;

use App\Http\Resources\Institution\DepartmentApplicationStepResource;
use App\Http\Resources\Students\AcademicLevelResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class EnrolmentResource extends JsonResource
{

    public function toArray(Request $request): array
    {
        return [
            'type' => 'enrolments',
            'id' => $this->id,
            'attributes' => [
                'studentId' => $this?->student?->id,
                'studentName' => $this->student?->user?->full_name,
                'studentNumber' => $this->student?->student_number,
                'requiredExamSittingCount' => $this->student?->required_exam_sitting_count ?? null,
                'applicationFeeProofOfPaymentId' => $this->application_fee_proof_of_payment_id,
                'tuitionFeeProofOfPaymentId' => $this->tuition_fee_proof_of_payment_id,
                'applicationFeePaid' => $this->application_fee_paid,
                'tuitionFeePaid' => $this->tuition_fee_paid,
                'institutionDepartmentId' => $this->institution_department_id,
                'department' => $this->institutionDepartment?->department?->name,
                'departmentLevelId' => $this->department_level_id,
                'level' => $this->departmentLevel?->level?->name,
                'departmentCourseId' => $this->department_course_id,
                'course' => $this->departmentCourse?->course?->name,
                'applicationTrackingNumber' => $this->application_tracking_number,
                'createdAt' => $this->created_at,
                'updatedAt' => $this->updated_at,
                'deletedAt' => $this->deleted_at,
            ],
            'relationships' => [
                'oLevelResults' => AcademicLevelResource::collection($this->student?->oLevelResults),
                'departmentWorkflowStep' => DepartmentApplicationStepResource::make($this->departmentWorkflowStep)
            ]
        ];
    }
}
