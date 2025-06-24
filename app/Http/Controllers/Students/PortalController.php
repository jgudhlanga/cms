<?php

namespace App\Http\Controllers\Students;

use App\DTO\Students\CreateApplicationDto;
use App\DTO\Users\UserDto;
use App\Enums\Shared\RoleEnum;
use App\Enums\Shared\TenantEnum;
use App\Http\Controllers\Controller;
use App\Http\Requests\Students\CreateApplicationRequest;
use App\Http\Requests\Users\CreateUserRequest;
use App\Http\Resources\Students\StudentResource;
use App\Jobs\Users\SendVerificationEmailJob;
use App\Models\Tenants\Tenant;
use App\Models\Users\User;
use App\Repositories\Students\interface\IStudentRepository;
use App\Repositories\Users\interface\IUserRepository;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class PortalController extends Controller
{
    public function __construct(protected IUserRepository $userRepository, protected IStudentRepository $studentRepository)
    {
    }

    public function dashboard()
    {
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
        return Inertia::render('portal/student/AddEditApplication');
    }

    public function storeApplication(CreateApplicationRequest $request)
    {
        $this->studentRepository->create(CreateApplicationDto::fromCreateApplicationRequest($request, request()->user()));
        // we need to and email with a tracking number
        // we should redirect to a confirmation page
        return to_route('portal.dashboard');
    }

    public function personal()
    {
        $user = request()->user();
        $student = StudentResource::make($user->studentProfile);
        return Inertia::render('portal/student/PersonalDetails', compact('student'));
    }

    public function programs()
    {
        return Inertia::render('portal/student/Programs');
    }

    public function contacts()
    {
        return Inertia::render('portal/student/Contacts');
    }

    public function sponsors()
    {
        return Inertia::render('portal/student/Sponsors');
    }

    public function financialRecord()
    {
        return Inertia::render('portal/student/FinancialRecord');
    }

    public function academicRecord()
    {
        return Inertia::render('portal/student/AcademicRecord');
    }
}
