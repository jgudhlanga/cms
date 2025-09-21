<?php

namespace App\Http\Controllers\Users;

use App\DTO\Users\UpdateUserDto;
use App\DTO\Users\UserDto;
use App\Enums\Shared\StatusEnum;
use App\Http\Controllers\Controller;
use App\Http\Filters\Users\UserFilter;
use App\Http\Requests\Users\UserRequest;
use App\Http\Requests\Users\UpdateUserRequest;
use App\Http\Resources\Users\UserResource;
use App\Models\Users\User;
use App\Repositories\Users\interface\IUserRepository;
use Inertia\Inertia;

class UserController extends Controller
{
    public function __construct(protected IUserRepository $repository)
    {
    }

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
        $user = new UserResource($user);
        return Inertia::render('users/Show', compact('user'));
    }

    public function edit(User $user)
    {
        $this->authorize('update', $user);
        $user = new UserResource($user);
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
}
