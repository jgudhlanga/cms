<?php

namespace App\Http\Controllers\Users;

use App\DTO\Institution\CreateStaffDto;
use App\DTO\Students\UpdateStudentDto;
use App\DTO\Users\UpdateUserDto;
use App\DTO\Users\UserDto;
use App\Enums\Shared\StatusEnum;
use App\Http\Controllers\Controller;
use App\Http\Filters\Users\UserFilter;
use App\Http\Requests\Institution\StaffRequest;
use App\Http\Requests\Students\UpdateStudentUserRequest;
use App\Http\Requests\Users\UpdateUserCredentialsRequest;
use App\Http\Requests\Users\UpdateUserRequest;
use App\Http\Requests\Users\UserRequest;
use App\Http\Resources\Users\UserResource;
use App\Models\Users\User;
use App\Repositories\Institution\interface\IStaffRepository;
use App\Repositories\Students\interface\IStudentRepository;
use App\Repositories\Users\interface\IUserRepository;
use Inertia\Inertia;

class UserController extends Controller
{
    public function __construct(
        protected IUserRepository $repository,
        protected IStaffRepository $staffRepository,
        protected IStudentRepository $studentRepository) {}

    public function index(UserFilter $filters)
    {
        $this->authorize('viewAny', User::class);
        $users = UserResource::collection($this->repository->allFilter(['*'], $filters));

        return Inertia::render('users/Index', [
            'users' => $users,
            'filters' => request()->only(['search', 'trashed']),
            'trashedCount' => $this->repository->allTrashed()->count(),
        ]);
    }

    public function create()
    {
        $this->authorize('create', User::class);

        return Inertia::render('users/Create', []);
    }

    public function store(UserRequest $request)
    {
        $this->authorize('create', User::class);
        $tenantId = request()->user()->tenant_id;
        $this->repository->create(UserDto::fromUserRequest($request, $tenantId, StatusEnum::ACTIVE->id()));
    }

    public function show(User $user)
    {
        $this->authorize('view', $user);
        $user->load([
            'roles',
            'preference',
            'staffProfile.contacts',
            'staffProfile.addresses',
            'studentProfile.contacts',
            'studentProfile.addresses',
        ]);
        $user = UserResource::make($user);

        return Inertia::render('users/Show', compact('user'));
    }

    public function edit(User $user)
    {
        // $this->authorize('update', $user);
        // $user->load('staffProfile');
        $user = UserResource::make($user);

        return Inertia::render('users/Edit', compact('user'));
    }

    public function update(UpdateUserRequest $request, User $user)
    {
        $this->authorize('update', $user);
        $this->repository->update($user, UpdateUserDto::fromUpdateUserRequest($request));
    }

    public function destroy(User $user)
    {
        $this->authorize('delete', $user);
        $this->repository->delete($user);
    }

    public function restore(string $id)
    {
        $user = $this->repository->findTrashed($id);
        $this->authorize('restore', $user);
        $this->repository->restore($user);
    }

    public function forceDelete(User $user)
    {
        $this->authorize('forceDelete', $user);
        $this->repository->delete($user, true);
    }

    public function storeStaffUser(StaffRequest $request)
    {
        $staff = $this->staffRepository->create(CreateStaffDto::fromStaffRequest($request));

        return to_route('users.show', ['user' => $staff->user_id]);
    }

    public function updateStaffUser(StaffRequest $request, User $user)
    {
        $staff = $user->staffProfile;
        $this->staffRepository->update(
            $staff,
            CreateStaffDto::fromStaffRequest($request)
        );

        return to_route('users.show', ['user' => $user->id]);
    }

    public function updateStudentUser(UpdateStudentUserRequest $request, User $user)
    {
        $student = $user->studentProfile;
        $this->studentRepository->update(
            $student,
            UpdateStudentDto::fromUpdateStudentUserRequest($request),
        );
    }

    public function updateUserCredentials(UpdateUserCredentialsRequest $request, User $user)
    {
        $this->authorize('updateCredentials', $user);
        $validated = $request->validated();
        $updates = [];

        if ($request->boolean('change_email') && ! empty($validated['email'])) {
            $updates['email'] = $validated['email'];
        }

        if ($request->boolean('change_password') && ! empty($validated['password'])) {
            $updates['password'] = $validated['password'];
        }

        if (! empty($updates)) {
            $user->update($updates);
        }
    }
}
