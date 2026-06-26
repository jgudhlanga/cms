<?php

namespace App\Http\Resources\Students;

use App\Http\Resources\Shared\AddressResource;
use App\Http\Resources\Shared\ContactResource;
use App\Http\Resources\Shared\NextOfKinResource;
use App\Http\Resources\Users\UserSummaryResource;
use App\Models\Students\StudentApplication;
use App\Models\Students\StudentEnrolment;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class StudentResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $profileSummary = $this->resolveProfileSummary();

        return [
            'type' => 'student',
            'id' => $this?->id ?? null,
            'attributes' => [
                'userId' => $this?->user_id ?? null,
                'titleId' => $this?->title_id ?? null,
                'title' => $this->title?->name ?? null,
                'genderId' => $this?->gender_id ?? null,
                'gender' => $this->gender?->title ?? null,
                'maritalStatusId' => $this?->marital_status_id ?? null,
                'maritalStatus' => $this->maritalStatus?->title ?? null,
                'raceId' => $this?->race_id ?? null,
                'race' => $this->race?->title ?? null,
                'idTypeId' => $this?->id_type_id ?? null,
                'idType' => $this?->idType?->name ?? null,
                'studentNumber' => $this?->student_number ?? null,
                'idNumber' => $this?->id_number ?? null,
                'passportNumber' => $this?->passport_number ?? null,
                'countryId' => $this?->country_id ?? null,
                'country' => $this->country?->name ?? null,
                'studyPermitNumber' => $this?->study_permit_number ?? null,
                'dateOfBirth' => $this?->date_of_birth ?? null,
                'religionId' => $this?->religion_id ?? null,
                'religion' => $this->religion?->name ?? null,
                'denomination' => $this?->denomination ?? null,
                'height' => $this?->height ?? null,
                'weight' => $this?->weight ?? null,
                'requiredExamSittingCount' => $this?->required_exam_sitting_count ?? null,
                'disabilityStatus' => $this?->disability_status,
                'createdAt' => $this->created_at,
                'updatedAt' => $this->updated_at,
                'deletedAt' => $this->deleted_at,
                'department' => $profileSummary['department'],
                'level' => $profileSummary['level'],
                'course' => $profileSummary['course'],
                'modeOfStudy' => $profileSummary['modeOfStudy'],
                'enrolmentStatus' => $profileSummary['enrolmentStatus'],
                'applicationStatus' => $profileSummary['applicationStatus'],
                'intakePeriod' => $profileSummary['intakePeriod'],
                'applicationTrackingNumber' => $profileSummary['applicationTrackingNumber'],
                'profileContext' => $profileSummary['profileContext'],
            ],
            'relationships' => [
                'user' => UserSummaryResource::make($this->user),
                'latestEnrolment' => $this->latestEnrolment ? StudentEnrolmentResource::make($this->latestEnrolment) : null,
                'latestApplication' => $this->latestApplication ? StudentApplicationResource::make($this->latestApplication) : null,
                'mainContact' => ContactResource::make($this->contacts->firstWhere('contact_is_main', 1)),
                'mainAddress' => AddressResource::make($this->addresses->firstWhere('address_is_main', 1)),
                'nextOfKin' => NextOfKinResource::make($this->nextOfKins->first()),
            ],
        ];
    }

    /**
     * @return array{
     *     department: ?string,
     *     level: ?string,
     *     course: ?string,
     *     modeOfStudy: ?string,
     *     enrolmentStatus: ?string,
     *     applicationStatus: ?string,
     *     intakePeriod: ?string,
     *     applicationTrackingNumber: ?string,
     *     profileContext: 'enrolled'|'applicant'|null
     * }
     */
    private function resolveProfileSummary(): array
    {
        $enrolment = $this->latestEnrolment;

        if ($enrolment instanceof StudentEnrolment) {
            return [
                'department' => $enrolment->institutionDepartment?->department?->name,
                'level' => $enrolment->departmentLevel?->level?->name,
                'course' => $enrolment->departmentCourse?->course?->name,
                'modeOfStudy' => $enrolment->modeOfStudy?->name,
                'enrolmentStatus' => $enrolment->studentEnrolmentStatus?->name,
                'applicationStatus' => null,
                'intakePeriod' => null,
                'applicationTrackingNumber' => null,
                'profileContext' => 'enrolled',
            ];
        }

        $application = $this->latestApplication;

        if ($application instanceof StudentApplication) {
            return [
                'department' => $application->institutionDepartment?->department?->name,
                'level' => $application->departmentLevel?->level?->name,
                'course' => $application->departmentCourse?->course?->name,
                'modeOfStudy' => $application->modeOfStudy?->name,
                'enrolmentStatus' => null,
                'applicationStatus' => $application->departmentWorkflowStep?->workflowStep?->name,
                'intakePeriod' => $application->intakePeriod?->name,
                'applicationTrackingNumber' => $application->application_tracking_number,
                'profileContext' => 'applicant',
            ];
        }

        return [
            'department' => null,
            'level' => null,
            'course' => null,
            'modeOfStudy' => null,
            'enrolmentStatus' => null,
            'applicationStatus' => null,
            'intakePeriod' => null,
            'applicationTrackingNumber' => null,
            'profileContext' => null,
        ];
    }
}
