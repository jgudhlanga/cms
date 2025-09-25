<?php

namespace App\Http\Resources\Students;

use App\Enums\Shared\FeeTypeEnum;
use App\Http\Resources\Institution\DepartmentApplicationStepResource;
use App\Http\Resources\Institution\DepartmentCourseResource;
use App\Http\Resources\Institution\DepartmentLevelResource;
use App\Http\Resources\Institution\InstitutionDepartmentResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @property mixed $institution_department_id
 * @property mixed $department_level_id
 * @property mixed $department_course_id
 * @property mixed $institutionDepartment
 * @property mixed $departmentLevel
 * @property mixed $departmentCourse
 * @property mixed $departmentWorkflowStep
 */
class StudentProgramResource extends JsonResource
{

    public function toArray(Request $request): array
    {
        return [
            'type' => 'student-program',
            'id' => $this->id,
            'attributes' => [
                'institutionDepartmentId' => $this->institution_department_id,
                'departmentLevelId' => $this->department_level_id,
                'departmentCourseId' => $this->department_course_id,
                'modeOfStudyId' => $this->mode_of_study_id,
                'modeOfStudy' => $this->modeOfStudy?->name,
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
                'student' => StudentResource::make($this->student),
                'institutionDepartment' => InstitutionDepartmentResource::make($this->institutionDepartment),
                'departmentLevel' => DepartmentLevelResource::make($this->departmentLevel),
                'departmentCourse' => DepartmentCourseResource::make($this->departmentCourse),
                'departmentWorkflowStep' => DepartmentApplicationStepResource::make($this->departmentWorkflowStep)
            ]
        ];
    }
}
