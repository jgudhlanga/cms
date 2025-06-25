<?php

namespace App\Http\Controllers\Students;

use App\DTO\Shared\AddressDto;
use App\DTO\Shared\ContactDto;
use App\DTO\Students\CreateApplicationDto;
use App\DTO\Users\UserDto;
use App\Enums\Shared\RoleEnum;
use App\Enums\Shared\TenantEnum;
use App\Http\Controllers\Controller;
use App\Http\Requests\Shared\AddressRequest;
use App\Http\Requests\Shared\ContactRequest;
use App\Http\Requests\Students\CreateApplicationRequest;
use App\Http\Requests\Users\CreateUserRequest;
use App\Http\Resources\Shared\AddressResource;
use App\Http\Resources\Shared\ContactResource;
use App\Http\Resources\Students\SponsorResource;
use App\Http\Resources\Students\StudentResource;
use App\Jobs\Users\SendVerificationEmailJob;
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
        return Inertia::render('portal/guest/ApplicationUserForm');
    }

    public function store(CreateUserRequest $request)
    {
        $tenant = Tenant::where('name', TenantEnum::HARARE_POLY->value)->first();
        $user = $this->userRepository->create(UserDto::fromCreateUserRequest($request, $tenant));
        $user->assignRole(RoleEnum::STUDENT);
        SendVerificationEmailJob::dispatch($user)->withoutDelay();
        Auth::login($user);
        return to_route('portal.confirmation', compact('user'));
    }

    public function confirmation(User $user)
    {
        $email = $user->email;
        return Inertia::render('portal/guest/Confirmation', compact('email'));
    }

    public function createApplication()
    {
        $this->authorize('manageStudentPersonalDetails');
        return Inertia::render('portal/student/AddEditApplication');
    }

    public function storeApplication(CreateApplicationRequest $request)
    {
        $this->authorize('manageStudentPersonalDetails');
        $this->studentRepository->create(CreateApplicationDto::fromCreateApplicationRequest($request, request()->user()));
        // we need to and email with a tracking number
        // we should redirect to a confirmation page
        return to_route('portal.dashboard');
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
        return Inertia::render('portal/student/Programs');
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
        return Inertia::render('portal/student/AcademicRecord');
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
