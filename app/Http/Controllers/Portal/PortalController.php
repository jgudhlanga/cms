<?php

namespace App\Http\Controllers\Portal;

use App\DTO\Students\CreateApplicationDto;
use App\DTO\Users\UserDto;
use App\Enums\RoleEnum;
use App\Enums\TenantEnum;
use App\Http\Controllers\Controller;
use App\Http\Requests\Students\CreateApplicationRequest;
use App\Http\Requests\Users\CreateUserRequest;
use App\Http\Resources\Users\UserResource;
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

    public function index(User $user)
    {
        $user = UserResource::make($user);
        return Inertia::render('portal/student/Index', [
            'user' => $user,
            'portal' => [],
            'applications' => [],
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
        return to_route('portal.confirmation', compact('user'));
    }

    public function confirmation(User $user)
    {
        $user = UserResource::make($user);
        return Inertia::render('portal/guest/Confirmation', compact('user'));
    }

    public function createApplication(User $user)
    {
        $user = UserResource::make($user);
        return Inertia::render('portal/student/AddEditApplication', compact('user'));
    }

    public function storeApplication(CreateApplicationRequest $request, User $user)
    {
        $this->studentRepository->create(CreateApplicationDto::fromCreateApplicationRequest($request, $user));
        // we need to and email with a tracking number
        // we should redirect to a confirmation page
        return to_route('portal.index', compact('user'));
    }

    public function personal(User $user)
    {
        $user = UserResource::make($user);
        return Inertia::render('portal/student/PersonalDetails', compact('user'));
    }

    public function programs(User $user)
    {
        $user = UserResource::make($user);
        return Inertia::render('portal/student/Programs', compact('user'));
    }

    public function contacts(User $user)
    {
        $user = UserResource::make($user);
        return Inertia::render('portal/student/Contacts', compact('user'));
    }

    public function sponsors(User $user)
    {
        $user = UserResource::make($user);
        return Inertia::render('portal/student/Sponsors', compact('user'));
    }

    public function financialRecord(User $user)
    {
        $user = UserResource::make($user);
        return Inertia::render('portal/student/FinancialRecord', compact('user'));
    }

    public function academicRecord(User $user)
    {
        $user = UserResource::make($user);
        return Inertia::render('portal/student/AcademicRecord', compact('user'));
    }
}
