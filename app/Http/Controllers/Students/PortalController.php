<?php

namespace App\Http\Controllers\Students;

use App\DTO\Shared\{AddressDto, ContactDto, NextOfKinDto};
use App\DTO\Students\CreateApplicationDto;
use App\DTO\Users\UserDto;
use App\Enums\Acl\RoleEnum;
use App\Helpers\WorkflowHelper;
use App\Models\Institution\DepartmentApplicationStep;
use App\Enums\Shared\{StatusEnum, TenantEnum};
use App\Events\Students\ApplicationWorkflowStepChanged;
use App\Http\Controllers\Controller;
use App\Http\Requests\Shared\{AddressRequest, ContactRequest, NextOfKinRequest};
use App\Http\Requests\Students\CreateApplicationRequest;
use App\Http\Requests\Users\UserRequest;
use App\Http\Resources\Students\{AcademicRecordResource, StudentProgramResource, StudentResource};
use App\Jobs\Users\SendVerificationEmailJob;
use App\Models\Shared\Status;
use App\Models\Tenants\Tenant;
use App\Models\Users\User;
use App\Repositories\Shared\interface\{IAddressRepository, IContactRepository, INextOfKinRepository};
use App\Repositories\Students\interface\IStudentRepository;
use App\Repositories\Users\interface\IUserRepository;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\{Auth, DB, Log};
use Inertia\Inertia;
use Throwable;

class PortalController extends Controller
{
    public function __construct(
        protected IUserRepository      $userRepository,
        protected IStudentRepository   $studentRepository,
        protected IContactRepository   $contactRepository,
        protected IAddressRepository   $addressRepository,
        protected INextOfKinRepository $nextOfKinRepository,
    )
    {
    }

    // ========= Dashboard and Registration =========

    public function dashboard()
    {
        $this->authorize('viewStudentDashboard');
        return Inertia::render('portal/student/Index', [
            'filters' => request()->only(['search', 'trashed']),
            'trashedCount' => 0,
        ]);
    }

    public function create()
    {
        $this->logoutIfAuthenticated();
        return Inertia::render('portal/guest/RegistrationUserForm');
    }

    public function store(UserRequest $request)
    {
        $tenant = Tenant::where('name', TenantEnum::HARARE_POLY->value)->first();
        $status = Status::where('title', StatusEnum::ACTIVE->value)->first();

        $user = $this->userRepository->create(
            UserDto::fromUserRequest($request, $tenant, $status)
        );

        $user->assignRole(RoleEnum::STUDENT);
        SendVerificationEmailJob::dispatch($user)->withoutDelay();

        Auth::login($user);
        return to_route('portal.confirmation', compact('user'));
    }

    public function registrationConfirmation(User $user)
    {
        return Inertia::render('portal/guest/RegistrationConfirmation', [
            'email' => $user->email,
        ]);
    }

    // ========= Application Workflow =========

    public function createApplication()
    {
        $this->authorize('manageStudentPersonalDetails');
        return Inertia::render('portal/student/AddEditApplication');
    }

    /**
     * @throws Throwable
     */
    public function storeApplication(CreateApplicationRequest $request)
    {
        $this->authorize('manageStudentPersonalDetails');

        $user = request()->user();

        DB::beginTransaction();
        try {
            $this->updateUserNamesIfChanged($user, $request);
            $student = $this->studentRepository->create(
                CreateApplicationDto::fromCreateApplicationRequest($request, $user)
            );
            $application = $student->programs()->latest()->first();
            $stepOne = WorkflowHelper::getDepartmentApplicationStepByPosition(1);
            $stepTwo = WorkflowHelper::getDepartmentApplicationStepByPosition(2);
            $application->update(['department_application_step_id' => $stepOne->id]);
            DB::commit();

            ApplicationWorkflowStepChanged::dispatch($student, $application, $stepTwo, $stepOne);
            /*SendApplicationSubmittedEmail::dispatch(
                $user->full_name,
                $user->email,
                $application->application_tracking_number
            )->withoutDelay();*/

            return to_route('portal.application-confirmation');
        } catch (Throwable $e) {
            DB::rollBack();
            Log::error('Application submission failed', ['exception' => $e]);
            return back()->withErrors([
                'error' => 'An error occurred while submitting your application. Please try again.',
            ]);
        }
    }

    public function applicationConfirmation(User $user)
    {
        $this->authorize('manageStudentPersonalDetails');
        return Inertia::render('portal/student/ApplicationConfirmation', [
            'student' => StudentResource::make($this->getStudent(request())),
        ]);
    }

    // ========= Student Profile =========

    public function personal()
    {
        $this->authorize('manageStudentPersonalDetails');
        return Inertia::render('portal/student/PersonalDetails', [
            'student' => StudentResource::make($this->getStudent(request())),
        ]);
    }

    public function programs()
    {
        $this->authorize('manageStudentProgramDetails');
        $student = $this->getStudent(request());

        return Inertia::render('portal/student/Programs', [
            'programs' => StudentProgramResource::collection($student->programs),
        ]);
    }

    public function academicRecord()
    {
        $this->authorize('manageStudentAcademicRecords');
        $student = $this->getStudent(request());

        return Inertia::render('portal/student/AcademicRecord', [
            'academicRecord' => AcademicRecordResource::collection($student->academicRecord),
        ]);
    }

    public function financialRecord()
    {
        $this->authorize('manageStudentFinancialRecords');
        return Inertia::render('portal/student/FinancialRecord');
    }

    // ========= Contact Details =========

    public function storeContactDetails(ContactRequest $request)
    {
        $this->authorize('manageStudentContacts');
        $this->contactRepository->create(
            $this->getStudent(request()),
            ContactDto::fromContactRequest($request)
        );
    }

    public function storeAddressDetails(AddressRequest $request)
    {
        $this->authorize('manageStudentContacts');
        $this->addressRepository->create(
            $this->getStudent(request()),
            AddressDto::fromAddressRequest($request)
        );
    }

    public function storeNextOfKinDetails(NextOfKinRequest $request)
    {
        $this->authorize('manageStudentContacts');
        $this->nextOfKinRepository->create(
            $this->getStudent(request()),
            NextOfKinDto::fromNextOfKinRequest($request)
        );
    }

    // ========= Helpers =========

    private function getStudent(Request $request)
    {
        return $request->user()->studentProfile;
    }

    private function updateUserNamesIfChanged(User $user, Request $request): void
    {
        $user->fill($request->only(['first_name', 'middle_name', 'last_name']));

        if ($user->isDirty(['first_name', 'middle_name', 'last_name'])) {
            $user->save();
            Auth::login($user); // Refresh session with updated info
        }
    }

    private function logoutIfAuthenticated(): void
    {
        if (Auth::check()) {
            Auth::logout();
            request()->session()->invalidate();
            request()->session()->regenerateToken();
        }
    }
}
