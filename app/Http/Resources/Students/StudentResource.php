<?php

namespace App\Http\Resources\Students;

use App\Http\Resources\Shared\AddressResource;
use App\Http\Resources\Shared\ContactResource;
use App\Http\Resources\Shared\NextOfKinResource;
use App\Http\Resources\Users\UserSummaryResource;
use App\Models\Students\StudentApplication;
use App\Models\Students\StudentApprentice;
use App\Models\Students\StudentEnrolment;
use App\Models\Students\StudentSponsor;
use App\Models\Students\StudentTransfer;
use App\Services\Students\ReturningStudentContextService;
use App\Services\Students\StudentIdNumberValidationService;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class StudentResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $this->resource->loadMissing([
            'title',
            'gender',
            'maritalStatus',
            'race',
            'idType',
            'country',
            'religion',
            'user.status',
            'contacts',
            'addresses',
            'nextOfKins.relationship',
            'nextOfKins.contacts',
            'nextOfKins.addresses',
            'latestEnrolment.institutionDepartment.department',
            'latestEnrolment.departmentLevel.level',
            'latestEnrolment.departmentCourse.course',
            'latestEnrolment.modeOfStudy',
            'latestEnrolment.studentEnrolmentStatus',
            'latestEnrolment.semester',
            'latestEnrolment.academicCalendar',
            'latestEnrolment.academicCalendarStudentEnrolment.academicCalendarClass.classConfig.syllabus',
            'latestEnrolment.studentApplication.workflowStep',
            'latestEnrolment.studentApplication.intakePeriod',
            'latestApplication.student.user',
            'latestApplication.institutionDepartment.department',
            'latestApplication.departmentLevel.level',
            'latestApplication.departmentCourse.course',
            'latestApplication.modeOfStudy',
            'latestApplication.workflowStep',
            'latestApplication.intakePeriod',
            'latestApplication.transfer',
            'latestEnrolment.studentApplication.transfer',
            'transfers.studentApplication',
        ]);

        $profileSummary = $this->resolveProfileSummary();
        $idNumberValidation = app(StudentIdNumberValidationService::class)->resolve($this->resource, $request->user());
        $apprenticeSummary = $this->resolveApprenticeSummary();
        $sponsorSummary = $this->resolveSponsorSummary();
        $transferSummary = $this->resolveTransferSummary();

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
                'idNumberValid' => $idNumberValidation['idNumberValid'],
                'suggestedIdNumber' => $idNumberValidation['suggestedIdNumber'],
                'idNumberRectificationStatus' => $idNumberValidation['idNumberRectificationStatus'],
                'idNumberConflict' => $idNumberValidation['idNumberConflict'],
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
                'isApprenticeThisYear' => $apprenticeSummary['isApprenticeThisYear'],
                'employer' => $apprenticeSummary['employer'],
                'apprenticeNumber' => $apprenticeSummary['apprenticeNumber'],
                'isSponsoredThisYear' => $sponsorSummary['isSponsoredThisYear'],
                'sponsor' => $sponsorSummary['sponsor'],
                'isTransferAtCurrentLevel' => $transferSummary['isTransferAtCurrentLevel'],
                'transferCollegeName' => $transferSummary['transferCollegeName'],
                'idPhotoUrl' => $this->idPhotoUrl('card'),
                'idPhotoThumbUrl' => $this->idPhotoUrl('thumb'),
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
            $enrolment->loadMissing([
                'institutionDepartment.department',
                'departmentLevel.level',
                'departmentCourse.course',
                'modeOfStudy',
                'studentEnrolmentStatus',
            ]);

            return [
                'department' => $enrolment->institutionDepartment?->department?->name,
                'level' => $enrolment->departmentLevel?->level?->name,
                'course' => $enrolment->departmentCourse?->course?->name,
                'modeOfStudy' => $enrolment->modeOfStudy?->name,
                'enrolmentStatus' => $enrolment->studentEnrolmentStatus?->name,
                'applicationStatus' => $this->resolveApplicationStatus($enrolment),
                'intakePeriod' => null,
                'applicationTrackingNumber' => null,
                'profileContext' => 'enrolled',
            ];
        }

        $application = $this->latestApplication;

        if ($application instanceof StudentApplication) {
            $application->loadMissing([
                'institutionDepartment.department',
                'departmentLevel.level',
                'departmentCourse.course',
                'modeOfStudy',
                'workflowStep',
                'intakePeriod',
            ]);

            return [
                'department' => $application->institutionDepartment?->department?->name,
                'level' => $application->departmentLevel?->level?->name,
                'course' => $application->departmentCourse?->course?->name,
                'modeOfStudy' => $application->modeOfStudy?->name,
                'enrolmentStatus' => null,
                'applicationStatus' => $application->workflowStep?->name,
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

    /**
     * Admissions workflow status for the enrolment shown in the header, falling back
     * to the latest application when the enrolment has no linked application.
     */
    private function resolveApplicationStatus(StudentEnrolment $enrolment): ?string
    {
        $application = $enrolment->studentApplication;

        if ($application instanceof StudentApplication) {
            $application->loadMissing('workflowStep');

            $status = $application->workflowStep?->name;

            if ($status !== null) {
                return $status;
            }
        }

        return $this->latestApplication?->workflowStep?->name;
    }

    /**
     * @return array{
     *     isApprenticeThisYear: bool,
     *     employer: ?string,
     *     apprenticeNumber: ?string
     * }
     */
    private function resolveApprenticeSummary(): array
    {
        $apprentice = app(ReturningStudentContextService::class)
            ->currentApprenticeForStudentProfile($this->resource);

        if (! $apprentice instanceof StudentApprentice) {
            return [
                'isApprenticeThisYear' => false,
                'employer' => null,
                'apprenticeNumber' => null,
            ];
        }

        return [
            'isApprenticeThisYear' => true,
            'employer' => $apprentice->employer,
            'apprenticeNumber' => $apprentice->apprentice_number,
        ];
    }

    /**
     * @return array{
     *     isSponsoredThisYear: bool,
     *     sponsor: ?string
     * }
     */
    private function resolveSponsorSummary(): array
    {
        $sponsor = app(ReturningStudentContextService::class)
            ->currentSponsorForStudentProfile($this->resource);

        if (! $sponsor instanceof StudentSponsor) {
            return [
                'isSponsoredThisYear' => false,
                'sponsor' => null,
            ];
        }

        return [
            'isSponsoredThisYear' => true,
            'sponsor' => $sponsor->sponsor,
        ];
    }

    /**
     * Transfer for the application/enrolment level currently shown in the profile header.
     *
     * @return array{
     *     isTransferAtCurrentLevel: bool,
     *     transferCollegeName: ?string
     * }
     */
    private function resolveTransferSummary(): array
    {
        $empty = [
            'isTransferAtCurrentLevel' => false,
            'transferCollegeName' => null,
        ];

        $departmentLevelId = $this->latestEnrolment?->department_level_id
            ?? $this->latestApplication?->department_level_id;

        if ($departmentLevelId === null) {
            return $empty;
        }

        $enrolmentApplication = $this->latestEnrolment?->studentApplication;
        $transfer = $enrolmentApplication?->transfer;

        if (
            $transfer instanceof StudentTransfer
            && (int) $enrolmentApplication->department_level_id === (int) $departmentLevelId
        ) {
            return $this->transferSummaryFromModel($transfer);
        }

        $latestApplication = $this->latestApplication;
        if (
            $latestApplication instanceof StudentApplication
            && (int) $latestApplication->department_level_id === (int) $departmentLevelId
            && $latestApplication->transfer instanceof StudentTransfer
        ) {
            return $this->transferSummaryFromModel($latestApplication->transfer);
        }

        $transfer = $this->transfers
            ->filter(function (StudentTransfer $candidate) use ($departmentLevelId): bool {
                return (int) ($candidate->studentApplication?->department_level_id) === (int) $departmentLevelId;
            })
            ->sortByDesc('id')
            ->first();

        if (! $transfer instanceof StudentTransfer) {
            return $empty;
        }

        return $this->transferSummaryFromModel($transfer);
    }

    /**
     * @return array{
     *     isTransferAtCurrentLevel: bool,
     *     transferCollegeName: ?string
     * }
     */
    private function transferSummaryFromModel(StudentTransfer $transfer): array
    {
        $collegeName = trim((string) $transfer->college_name);

        if ($collegeName === '') {
            return [
                'isTransferAtCurrentLevel' => false,
                'transferCollegeName' => null,
            ];
        }

        return [
            'isTransferAtCurrentLevel' => true,
            'transferCollegeName' => $collegeName,
        ];
    }

    private function idPhotoUrl(string $conversion): ?string
    {
        $photo = $this->resource->latestIdPhoto();
        if (! $photo instanceof Media) {
            return null;
        }

        return $photo->getFullUrl($conversion) ?: $photo->getFullUrl();
    }
}
