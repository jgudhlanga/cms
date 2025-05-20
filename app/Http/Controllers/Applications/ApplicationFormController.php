<?php

namespace App\Http\Controllers\Applications;

use App\DTO\Users\UserDto;
use App\Enums\RoleEnum;
use App\Enums\TenantEnum;
use App\Http\Controllers\Controller;
use App\Http\Requests\Users\CreateUserRequest;
use App\Http\Resources\Users\UserResource;
use App\Models\Tenants\Tenant;
use App\Models\Users\User;
use App\Repositories\Applications\interface\IApplicationRepository;
use App\Repositories\Users\interface\IUserRepository;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class ApplicationFormController extends Controller
{

    public function __construct(protected IUserRepository $userRepository, protected IApplicationRepository $applicationRepository)
    {

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
        return Inertia::render('applications/ApplicationForm');
    }

    public function store(CreateUserRequest $request)
    {
        $tenant = Tenant::where('name', TenantEnum::HARARE_POLY->value)->first();
        $user = $this->userRepository->create(UserDto::fromCreateUserRequest($request, $tenant));
        $user->assignRole(RoleEnum::STUDENT);
        return to_route('applications.confirmation', compact('user'));
    }

    public function confirmation(User $user)
    {
        $user = UserResource::make($user);
        return Inertia::render('applications/Confirmation', compact('user'));
    }
}
