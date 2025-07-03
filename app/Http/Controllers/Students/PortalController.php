<?php

namespace App\Http\Controllers\Students;

use App\DTO\Shared\AddressDto;
use App\DTO\Shared\ContactDto;
use App\DTO\Students\CreateApplicationDto;
use App\DTO\Users\UserDto;
use App\Enums\Shared\RoleEnum;
use App\Enums\Shared\StatusEnum;
use App\Enums\Shared\TenantEnum;
use App\Http\Controllers\Controller;
use App\Http\Requests\Shared\AddressRequest;
use App\Http\Requests\Shared\ContactRequest;
use App\Http\Requests\Students\CreateApplicationRequest;
use App\Http\Requests\Users\UserRequest;
use App\Http\Resources\Shared\AddressResource;
use App\Http\Resources\Shared\ContactResource;
use App\Http\Resources\Students\AcademicRecordResource;
use App\Http\Resources\Students\SponsorResource;
use App\Http\Resources\Students\StudentProgramResource;
use App\Http\Resources\Students\StudentResource;
use App\Jobs\Students\SendApplicationSubmittedEmail;
use App\Jobs\Users\SendVerificationEmailJob;
use App\Models\Shared\Status;
use App\Models\Tenants\Tenant;
use App\Models\Users\User;
use App\Repositories\Shared\interface\IAddressRepository;
use App\Repositories\Shared\interface\IContactRepository;
use App\Repositories\Students\interface\IStudentRepository;
use App\Repositories\Users\interface\IUserRepository;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class PortalController extends Controller
{
    public function __construct(
        protected IUserRepository    $userRepository,
        protected IStudentRepository $studentRepository,
        protected IContactRepository $contactRepository,
        protected IAddressRepository $addressRepository,
    )
    {
    }

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
        // Check if the user is authenticated
        if (Auth::check()) {
            // Log out the user
            Auth::logout();
            // Optionally, invalidate the session and regenerate the token
            request()->session()->invalidate();
            request()->session()->regenerateToken();
        }
        return Inertia::render('portal/guest/RegistrationUserForm');
    }

    public function store(UserRequest $request)
    {
        $tenant = Tenant::where('name', TenantEnum::HARARE_POLY->value)->first();
        $status = Status::where('title', StatusEnum::ACTIVE->value)->first();
        $user = $this->userRepository->create(UserDto::fromUserRequest($request, $tenant, $status));
        $user->assignRole(RoleEnum::STUDENT);
        SendVerificationEmailJob::dispatch($user)->withoutDelay();
        Auth::login($user);
        return to_route('portal.confirmation', compact('user'));
    }

    public function registrationConfirmation(User $user)
    {
        $email = $user->email;
        return Inertia::render('portal/guest/RegistrationConfirmation', compact('email'));
    }

    public function createApplication()
    {
        $this->authorize('manageStudentPersonalDetails');
        return Inertia::render('portal/student/AddEditApplication');
    }

    public function storeApplication(CreateApplicationRequest $request)
    {
        $this->authorize('manageStudentPersonalDetails');
        $user = request()->user();
        $student = $this->studentRepository->create(CreateApplicationDto::fromCreateApplicationRequest($request, $user));
        # send an email with a tracking number
        $name = $user->full_name;
        $email = $user->email;
        # get the latest created student application
        $application = $student->programs()->latest()->first();
        $trackingNumber = $application->application_tracking_number;
        SendApplicationSubmittedEmail::dispatch($name, $email, $trackingNumber)->withoutDelay();
        # we should redirect to a confirmation page
        return to_route('portal.application-confirmation');
    }

    public function applicationConfirmation(User $user)
    {
        $this->authorize('manageStudentPersonalDetails');
        $student = StudentResource::make($this->getStudent(request()));
        return Inertia::render('portal/student/ApplicationConfirmation', compact('student'));
    }

    public function personal()
    {
        $this->authorize('manageStudentPersonalDetails');
        $student = StudentResource::make($this->getStudent(request()));
        return Inertia::render('portal/student/PersonalDetails', compact('student'));
    }

    public function programs()
    {
        $this->authorize('manageStudentProgramDetails');
        $student = $this->getStudent(request());
        $programs = StudentProgramResource::collection($student->programs);
        return Inertia::render('portal/student/Programs', compact('programs'));
    }

    public function contacts()
    {
        $student = $this->getStudent(request());
        $addresses = AddressResource::collection($student->addresses);
        $contacts = ContactResource::collection($student->contacts);
        $this->authorize('manageStudentContacts');
        return Inertia::render('portal/student/Contacts', compact('addresses', 'contacts'));
    }

    public function sponsors()
    {
        $this->authorize('manageStudentSponsors');
        $student = $this->getStudent(request());
        $sponsors = SponsorResource::collection($student->sponsors);
        return Inertia::render('portal/student/Sponsors', compact('sponsors'));
    }

    public function financialRecord()
    {
        $this->authorize('manageStudentFinancialRecords');
        return Inertia::render('portal/student/FinancialRecord');
    }

    public function academicRecord()
    {
        $this->authorize('manageStudentAcademicRecords');
        $student = $this->getStudent(request());
        $academicRecord = AcademicRecordResource::collection($student->academicRecord);
        return Inertia::render('portal/student/AcademicRecord', compact('academicRecord'));
    }

    private function getStudent(Request $request)
    {
        return $request->user()->studentProfile;
    }

    public function storeContactDetails(ContactRequest $request)
    {
        $student = $this->getStudent(request());
        $this->authorize('manageStudentContacts');
        $this->contactRepository->create($student, ContactDto::fromContactRequest($request));
    }

    public function storeAddressDetails(AddressRequest $request)
    {
        $student = $this->getStudent(request());
        $this->authorize('manageStudentContacts');
        $this->addressRepository->create($student, AddressDto::fromAddressRequest($request));
    }
}
