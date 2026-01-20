<?php

namespace App\Http\Controllers\Api\V1\Users;

use App\DTO\Users\UpdateUserDto;
use App\DTO\Users\UserDto;
use App\Enums\Shared\StatusEnum;
use App\Enums\Shared\TenantEnum;
use App\Http\Controllers\Api\V1\Utils\ApiDropdownController;
use App\Http\Filters\Users\UserFilter;
use App\Http\Requests\Institution\StaffRequest;
use App\Http\Requests\Users\UpdateUserRequest;
use App\Http\Requests\Users\UserRequest;
use App\Http\Resources\Users\UserResource;
use App\Repositories\Users\interface\IUserRepository;
use App\Traits\HttpUtil;
use Illuminate\Database\Eloquent\Attributes\UseResource;
use Inertia\Inertia;
use App\Models\Users\User;

class UserController extends ApiDropdownController
{
    use HttpUtil;

    public function __construct(protected IUserRepository $repository)
    {

    }

    public function index(UserFilter $filters)
    {
        $this->authorize('viewAny', User::class);
        return UserResource::collection($this->repository->allFilter(['*'], $filters))->additional([
            'filters' => request()->only(['search', 'trashed']),
            'trashedCount' => $this->repository->allTrashed()->count(),
        ]);
    }

    public function create()
    {
        $this->authorize('create', User::class);
        return Inertia::render('users/Create');
    }

    public function show(User $user)
    {
        $this->authorize('view', $user);
        $user = UserResource::make($user);
        return Inertia::render('users/Show', compact('user'));
    }

    public function edit(User $user)
    {
        $this->authorize('update', $user);
        $user = UserResource::make($user);
        return Inertia::render('users/Edit', compact('user'));
    }

    /**
     * Store a newly created staff.
     */
    public function store(UserRequest $request)
    {
        $this->authorize('create', User::class);
        $user = $this->repository->create(
            UserDto::fromUserRequest($request, TenantEnum::HARARE_POLY->id(), StatusEnum::ACTIVE->id())
        );
        return to_route('users.show', $user->id);
    }

    /**
     * Update the specified staff.
     */
    public function update(UpdateUserRequest $request, User $user)
    {
        $this->authorize('update', $user);
        $this->repository->update(
            $user,
            UpdateUserDto::fromUpdateUserRequest($request)
        );
        return to_route('staff.show', $user->id);
    }

    /**
     * Soft delete the specified staff.
     */
    public function destroy(User $user)
    {
        $this->authorize('delete', $user);
        $this->repository->delete($user);
    }

    /**
     * Restore a soft-deleted staff.
     */
    public function restore(string $id)
    {
        $this->authorize('restore', User::class);
        $user = $this->repository->findTrashed($id);
        $this->repository->restore($user);
    }

    /**
     * Permanently delete the specified staff.
     */
    public function forceDelete(User $user)
    {
        $this->authorize('force', $user);
        $this->repository->delete($user, true);
    }
}
