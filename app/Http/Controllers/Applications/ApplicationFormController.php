<?php

namespace App\Http\Controllers\Applications;

use App\DTO\Users\UserDto;
use App\Http\Controllers\Controller;
use App\Http\Requests\Users\CreateUserRequest;
use App\Repositories\Applications\interface\IApplicationRepository;
use App\Repositories\Users\interface\IUserRepository;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class ApplicationFormController extends Controller
{

    public function __construct(protected IUserRepository $userRepository, protected IApplicationRepository $applicationRepository)
    {

    }

    public function index()
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
        $user = $this->userRepository->create(UserDto::fromCreateUserRequest($request));

    }
}
