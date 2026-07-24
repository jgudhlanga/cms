<?php

namespace App\Http\Resources\Enrolments;

use App\Enums\Shared\FeeTypeEnum;
use App\Http\Resources\Institution\CourseRequirementResource;
use App\Http\Resources\Institution\DepartmentApplicationStepResource;
use App\Http\Resources\Institution\DepartmentLevelRequirementResource;
use App\Http\Resources\Integrations\LedgerResource;
use App\Http\Resources\Students\AcademicLevelResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class EnrolmentResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $this->resource->loadMissing([
            'student.user',
            'student.idType',
            'student.country',
            'student.contacts',
            'student.oLevelResults.academicLevel',
            'student.oLevelResults.subject',
            'student.oLevelResults.grade',
            'modeOfStudy',
            'institutionDepartment.department',
            'departmentLevel.level',
            'departmentLevel.requirement',
            'departmentCourse.course',
            'departmentCourse.requirement',
            'intakePeriod',
            'classList',
            'departmentWorkflowStep.workflowStep',
            'departmentWorkflowStep.metadata',
        ]);

        $contact = $this->student?->contacts?->first();

        return [
            'type' => 'enrolments',
            'id' => $this->id,
            'attributes' => [
                'studentId' => $this?->student?->id,
                'studentName' => $this->student?->user?->full_name,
                'studentNumber' => $this->student?->student_number,
                'idNumber' => $this->student?->id_number,
                'passportNumber' => $this->student?->passport_number,
                'idType' => $this->student->idType?->name,
                'idTypeId' => $this->student->id_type_id,
                'country' => $this->student?->country?->name,
                'countryId' => $this->student?->country_id,
                'phoneNumber' => $contact?->phone_number,
                'email' => $this->student->user->email,
                'requiredExamSittingCount' => $this->student?->required_exam_sitting_count ?? null,
                'modeOfStudyId' => $this->mode_of_study_id,
                'modeOfStudy' => $this->modeOfStudy?->name,
                'institutionDepartmentId' => $this->institution_department_id,
                'department' => $this->institutionDepartment?->department?->name,
                'departmentLevelId' => $this->department_level_id,
                'level' => $this->departmentLevel?->level?->name,
                'levelId' => $this->departmentLevel?->level?->id,
                'intakePeriod' => $this->intakePeriod?->name,
                'intakePeriodId' => $this->intakePeriod?->id,
                'intakePeriodCalendarYear' => $this->intakePeriod?->calendar_year,
                'intakePeriodStartDate' => $this->intakePeriod?->start_date,
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
                'disabilityStatus' => $this->student?->disability_status,
                'createdAt' => $this->created_at,
                'updatedAt' => $this->updated_at,
                'deletedAt' => $this->deleted_at,
            ],
            'relationships' => [
                'classList' => $this->classList ? ClassListResource::make($this->classList) : null,
                'registrationReceipt' => $this->hasPaid(FeeTypeEnum::APPLICATION_FEE) ? LedgerResource::make($this->receipt(FeeTypeEnum::APPLICATION_FEE)) : null,
                'tuitionReceipt' => $this->hasPaid(FeeTypeEnum::TUITION_FEE) ? LedgerResource::make($this->receipt(FeeTypeEnum::TUITION_FEE)) : null,
                'oLevelResults' => AcademicLevelResource::collection($this->student?->oLevelResults),
                'departmentWorkflowStep' => DepartmentApplicationStepResource::make($this->departmentWorkflowStep),
                'requirements' => $this->departmentLevel?->requirement ? DepartmentLevelRequirementResource::make($this->departmentLevel->requirement) : null,
                'courseRequirements' => $this->departmentCourse?->requirement ? CourseRequirementResource::make($this->departmentCourse->requirement) : null,
            ],
        ];
    }
}
