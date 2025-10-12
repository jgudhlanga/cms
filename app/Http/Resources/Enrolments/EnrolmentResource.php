<?php

namespace App\Http\Resources\Enrolments;

use App\Enums\Shared\FeeTypeEnum;
use App\Http\Resources\Institution\DepartmentApplicationStepResource;
use App\Http\Resources\Integrations\LedgerResource;
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
                'phoneNumber' => $this->student?->user?->phone_number,
                'email' => $this->student->user->email,
                'requiredExamSittingCount' => $this->student?->required_exam_sitting_count ?? null,
                'modeOfStudyId' => $this->mode_of_study_id,
                'modeOfStudy' => $this->modeOfStudy?->name,
                'institutionDepartmentId' => $this->institution_department_id,
                'department' => $this->institutionDepartment?->department?->name,
                'departmentLevelId' => $this->department_level_id,
                'level' => $this->departmentLevel?->level?->name,
                'levelId' => $this->departmentLevel?->level?->id,
                'allowedApplicationsPerLevel' => $this->departmentLevel?->level?->allowed_applications_per_level,
                'departmentCourseId' => $this->department_course_id,
                'course' => $this->departmentCourse?->course?->name,
                'hasEnrolmentRequirements' => $this->departmentCourse?->course?->has_enrolment_requirements,
                'applicationTrackingNumber' => $this->application_tracking_number,
                'registrationFeePaid' => $this->hasPaid(FeeTypeEnum::APPLICATION_FEE),
                'tuitionFeePaid' => $this->hasPaid(FeeTypeEnum::TUITION_FEE),
                'registrationFeeConfirmed' => $this->registration_fee_confirmed,
                'tuitionFeeConfirmed' => $this->tuition_fee_confirmed,
                'requiredLevelCompleted' => $this->required_level_completed,
                'readWriteAcknowledged' => $this->read_write_acknowledged,
                'createdAt' => $this->created_at,
                'updatedAt' => $this->updated_at,
                'deletedAt' => $this->deleted_at,
            ],
            'relationships' => [
                'registrationReceipt' => $this->hasPaid(FeeTypeEnum::APPLICATION_FEE) ? LedgerResource::make($this->receipt(FeeTypeEnum::APPLICATION_FEE)) : null,
                'tuitionReceipt' => $this->hasPaid(FeeTypeEnum::TUITION_FEE) ? LedgerResource::make($this->receipt(FeeTypeEnum::TUITION_FEE)) : null,
                'oLevelResults' => AcademicLevelResource::collection($this->student?->oLevelResults),
                'departmentWorkflowStep' => DepartmentApplicationStepResource::make($this->departmentWorkflowStep),
                'requirements' => $this->departmentLevel?->requirement ? DepartmentApplicationStepResource::make($this->departmentLevel->requirement) : null,
            ]
        ];
    }
}
