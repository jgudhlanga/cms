<?php

namespace App\Http\Controllers\Api\V1\HMS;

use App\Enums\HMS\HostelApplicationStatusEnum;
use App\Models\HMS\HostelApplication;
use App\Models\Students\Student;
use App\Services\HMS\HostelApplicationApprovalOptionsService;
use App\Services\HMS\HostelApplicationEligibilityService;
use App\Services\HMS\HostelApplicationPendingService;
use App\Services\HMS\HostelApplicationSemesterService;
use App\Services\HMS\HostelRoomAvailabilityService;
use App\Services\HMS\HostelStudentAllocationService;
use App\Services\HMS\StudentAccommodationFeeService;
use App\Services\HMS\StudentPhysicalAddressFormatter;
use App\Support\HMS\HmsStudentAccess;
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
        protected HostelStudentAllocationService $allocationService,
        protected HostelApplicationApprovalOptionsService $approvalOptionsService,
        protected StudentAccommodationFeeService $accommodationFeeService,
    ) {}

    public function studentLookup(Request $request): MetaResponse
    {
        abort_unless($request->user() !== null, 403);

        $studentId = data_get($request->input('filter'), 'student');

        if (is_numeric($studentId) && (int) $studentId > 0) {
            $student = Student::query()
                ->with($this->studentLookupRelations())
                ->find((int) $studentId);

            if ($student === null) {
                return MetaResponse::make([
                    'found' => false,
                    'message' => __('hms.student_not_found'),
                ]);
            }

            abort_unless(HmsStudentAccess::canViewStudentHms($request->user(), $student), 403);

            return MetaResponse::make($this->buildStudentLookupMeta($student));
        }

        abort_unless(
            HmsStudentAccess::isStaffHmsUser($request->user())
                || $request->user()->can('create:hostel-applications'),
            403,
        );

        $search = trim((string) data_get($request->input('filter'), 'search', ''));

        if ($search === '') {
            return MetaResponse::make([
                'found' => false,
                'message' => __('hms.student_search_required'),
            ]);
        }

        $student = Student::query()
            ->with($this->studentLookupRelations())
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

        return MetaResponse::make($this->buildStudentLookupMeta($student));
    }

    public function selfLookup(Request $request): MetaResponse
    {
        abort_unless($request->user() !== null, 403);

        $student = $request->user()->studentProfile;

        if ($student === null) {
            return MetaResponse::make([
                'found' => false,
                'message' => __('hms.student_not_found'),
            ]);
        }

        abort_unless(HmsStudentAccess::canViewStudentHms($request->user(), $student), 403);

        $student->load($this->studentLookupRelations());

        return MetaResponse::make($this->buildStudentLookupMeta($student));
    }

    public function accommodationFees(Request $request): MetaResponse
    {
        abort_unless($request->user() !== null, 403);

        $studentId = HmsStudentAccess::studentIdFromRequest();

        if ($studentId === null) {
            abort(422, __('hms.student_required'));
        }

        $student = Student::query()->find($studentId);

        if ($student === null) {
            abort(404);
        }

        abort_unless(HmsStudentAccess::canViewStudentHms($request->user(), $student), 403);

        return MetaResponse::make(
            $this->accommodationFeeService->summaryForStudent($student),
        );
    }

    /**
     * @return array<string, mixed>
     */
    private function buildStudentLookupMeta(Student $student): array
    {
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

        $allocationBlocker = $this->allocationService->studentHasOpenAllocation((int) $student->id)
            ? HostelStudentAllocationService::BLOCKER_STUDENT_ALREADY_ALLOCATED
            : null;

        $blockers = array_values(array_filter([
            $semesterDates['blocker'],
            $roomAvailability['blocker'],
            $pendingBlocker,
            $allocationBlocker,
        ]));

        $eligibilityPassed = $this->eligibilityService->allPassed($eligibility);

        $canSubmit = $semesterDates['success']
            && $roomAvailability['blocker'] === null
            && $pendingBlocker === null
            && $allocationBlocker === null
            && $eligibilityPassed;

        return [
            'found' => true,
            'canSubmit' => $canSubmit,
            'canApply' => $canSubmit,
            'applyBlockers' => $blockers,
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
            'eligibilityPassed' => $eligibilityPassed,
        ];
    }

    /**
     * @return list<string>
     */
    private function studentLookupRelations(): array
    {
        return [
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
        ];
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

    public function approvalRooms(HostelApplication $hostelApplication, Request $request): MetaResponse
    {
        abort_unless($request->user() !== null, 403);

        $hostelApplication->load(['student.gender', 'gender']);

        $hostelId = $request->query('hostelId');
        if (! is_numeric($hostelId) || (int) $hostelId < 1) {
            return MetaResponse::make([
                'rooms' => [],
            ]);
        }

        return MetaResponse::make([
            'rooms' => $this->approvalOptionsService->roomsForApplication(
                $hostelApplication,
                (int) $hostelId,
            ),
        ]);
    }

    public function pendingQueue(Request $request): MetaResponse
    {
        abort_unless($request->user() !== null, 403);

        $excludeId = $request->query('exclude');
        $parsedExcludeId = is_numeric($excludeId) ? (int) $excludeId : null;

        $applications = HostelApplication::query()
            ->with(['student.user', 'gender'])
            ->whereIn('status', [
                HostelApplicationStatusEnum::PENDING,
                HostelApplicationStatusEnum::AWAITING_PAYMENT,
            ])
            ->when($parsedExcludeId !== null, fn ($query) => $query->where('id', '!=', $parsedExcludeId))
            ->orderByDesc('created_at')
            ->limit(5)
            ->get()
            ->map(fn (HostelApplication $application): array => [
                'id' => (int) $application->id,
                'displayName' => $application->type?->value === 'guest'
                    ? (string) $application->name
                    : (string) ($application->student?->user?->full_name ?? $application->name ?? $application->student?->student_number),
                'studentNumber' => $application->student?->student_number,
                'status' => $application->status?->value,
            ])
            ->values()
            ->all();

        return MetaResponse::make([
            'applications' => $applications,
        ]);
    }
}
