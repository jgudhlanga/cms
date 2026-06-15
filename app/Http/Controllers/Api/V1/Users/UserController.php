<?php

namespace App\Http\Controllers\Api\V1\Users;

use App\DTO\Users\UpdateUserDto;
use App\DTO\Users\UserDto;
use App\Enums\Shared\StatusEnum;
use App\Enums\Shared\TenantEnum;
use App\Http\Controllers\Api\V1\Utils\ApiDropdownController;
use App\Http\Filters\Users\UserFilter;
use App\Http\Requests\Preferences\UserPreferenceRequest;
use App\Http\Requests\Users\UpdateUserRequest;
use App\Http\Requests\Users\UserRequest;
use App\Http\Resources\Acl\PermissionResource;
use App\Http\Resources\AuditTrail\AuditTrailResource;
use App\Http\Resources\Preferences\UserPreferenceResource;
use App\Http\Resources\Users\UserResource;
use App\Models\Preferences\UserPreference;
use App\Models\Users\User;
use App\Repositories\Users\interface\IUserRepository;
use App\Traits\HttpUtil;
use Inertia\Inertia;

class UserController extends ApiDropdownController
{
    use HttpUtil;

    public function __construct(protected IUserRepository $repository) {}

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

    public function getUserPermissions(User $user)
    {
        $this->authorize('view', $user);
        $permissions = $user->getAllPermissions();

        return PermissionResource::collection($permissions);
    }

    public function getUserActivities(User $user)
    {
        $this->authorize('view', $user);

        $activities = $user->activities()
            ->latest()
            ->paginate(request()->integer('per_page', 20));

        return AuditTrailResource::collection($activities);
    }

    public function updateUserPreferences(UserPreferenceRequest $request, User $user)
    {
        $this->authorize('update', $user);

        $preference = UserPreference::query()->updateOrCreate(
            ['user_id' => $user->id],
            $this->validatedPreferencePayload($request),
        );

        return UserPreferenceResource::make($preference);
    }

    private function validatedPreferencePayload(UserPreferenceRequest $request): array
    {
        $payload = [];

        if ($request->has('side_bar_state')) {
            $payload['side_bar_state'] = $request->boolean('side_bar_state');
        }

        if ($request->filled('locale')) {
            $payload['locale'] = $request->string('locale')->toString();
        }

        return $payload;
    }
}
