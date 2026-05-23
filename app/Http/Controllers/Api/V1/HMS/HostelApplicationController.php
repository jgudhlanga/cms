<?php

namespace App\Http\Controllers\Api\V1\HMS;

use App\Models\HMS\HostelApplication;
use App\Models\Students\Student;
use App\Services\HMS\HostelApplicationApprovalOptionsService;
use App\Services\HMS\HostelApplicationEligibilityService;
use App\Services\HMS\HostelApplicationPendingService;
use App\Services\HMS\HostelApplicationSemesterService;
use App\Services\HMS\HostelRoomAvailabilityService;
use App\Services\HMS\StudentPhysicalAddressFormatter;
use Carbon\Carbon;
use Illuminate\Http\Request;
use LaravelJsonApi\Core\Responses\MetaResponse;
use LaravelJsonApi\Laravel\Http\Controllers\JsonApiController;

class HostelApplicationController extends JsonApiController
{
    public function __construct(
        protected HostelApplicationEligibilityService $eligibilityService,
        protected HostelApplicationSemesterService $semesterService,
        protected HostelRoomAvailabilityService $roomAvailabilityService,
        protected HostelApplicationPendingService $pendingService,
        protected HostelApplicationApprovalOptionsService $approvalOptionsService,
    ) {}

    public function studentLookup(Request $request): MetaResponse
    {
        abort_unless($request->user() !== null, 403);

        $search = trim((string) data_get($request->input('filter'), 'search', ''));

        if ($search === '') {
            return MetaResponse::make([
                'found' => false,
                'message' => __('hms.student_search_required'),
            ]);
        }

        $student = Student::query()
            ->with([
                'user',
                'gender',
                'addresses',
                'contacts',
                'nextOfKins.contacts',
                'latestEnrolment.modeOfStudy',
                'latestEnrolment.departmentCourse.course',
                'latestEnrolment.departmentLevel.level',
                'latestEnrolment.studentProgram.intakePeriod',
                'latestEnrolment.academicCalendar',
            ])
            ->where(function ($query) use ($search): void {
                $query->where('student_number', 'like', "%{$search}%")
                    ->orWhere('id_number', 'like', "%{$search}%")
                    ->orWhere('passport_number', 'like', "%{$search}%")
                    ->orWhereHas('user', function ($userQuery) use ($search): void {
                        $userQuery
                            ->where('first_name', 'like', "%{$search}%")
                            ->orWhere('middle_name', 'like', "%{$search}%")
                            ->orWhere('last_name', 'like', "%{$search}%");
                    });
            })
            ->first();

        if ($student === null) {
            return MetaResponse::make([
                'found' => false,
                'message' => __('hms.student_not_found'),
            ]);
        }

        $enrolment = $student->latestEnrolment;
        $eligibility = $this->eligibilityService->evaluate($student, $enrolment);
        $nextOfKin = $student->nextOfKins->first();
        $nextOfKinContact = $nextOfKin?->contacts->first();
        $studentContact = $student->contacts->first();
        $address = StudentPhysicalAddressFormatter::fromStudent($student);
        $asOf = Carbon::now((string) config('app.timezone'));
        $semesterDates = $this->semesterService->datesForApplication($student, $asOf);
        $roomAvailability = $this->roomAvailabilityService->summaryForGender($student->gender_id);

        $pendingBlocker = $this->pendingService->studentHasPendingApplication((int) $student->id)
            ? HostelApplicationPendingService::BLOCKER_PENDING_APPLICATION
            : null;

        $blockers = array_values(array_filter([
            $semesterDates['blocker'],
            $roomAvailability['blocker'],
            $pendingBlocker,
        ]));

        $canSubmit = $semesterDates['success']
            && $roomAvailability['blocker'] === null
            && $pendingBlocker === null;

        return MetaResponse::make([
            'found' => true,
            'canSubmit' => $canSubmit,
            'message' => __('hms.student_found'),
            'blockers' => $blockers,
            'student' => [
                'id' => $student->id,
                'studentNumber' => $student->student_number,
                'name' => $student->user?->full_name,
                'genderId' => $student->gender_id,
                'gender' => $student->gender?->title,
                'phoneNumber' => $studentContact?->phone_number,
                'physicalAddress' => $address,
                'emailAddress' => $student->user?->email,
                'course' => $enrolment?->departmentCourse?->course?->name,
                'level' => $enrolment?->departmentLevel?->level?->name,
                'studentEnrolmentId' => $enrolment?->id,
                'nextOfKinName' => $nextOfKin?->name,
                'nextOfKinContact' => $nextOfKinContact?->phone_number,
            ],
            'semester' => $semesterDates['success'] ? [
                'checkIn' => $semesterDates['checkIn'],
                'checkOut' => $semesterDates['checkOut'],
                'label' => $semesterDates['label'],
            ] : null,
            'roomAvailability' => [
                'availableBeds' => $roomAvailability['availableBeds'],
                'hostels' => $roomAvailability['hostels'],
                'roomCount' => $roomAvailability['roomCount'],
            ],
            'eligibility' => $eligibility,
            'eligibilityPassed' => $this->eligibilityService->allPassed($eligibility),
        ]);
    }

    public function approvalOptions(HostelApplication $hostelApplication, Request $request): MetaResponse
    {
        abort_unless($request->user() !== null, 403);

        $hostelApplication->load(['student.gender', 'gender']);

        $hostelId = $request->query('hostelId');
        $parsedHostelId = is_numeric($hostelId) ? (int) $hostelId : null;

        return MetaResponse::make(
            $this->approvalOptionsService->forApplication($hostelApplication, $parsedHostelId)
        );
    }
}
