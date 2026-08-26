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
use App\Http\Requests\Users\UserActivityIndexRequest;
use App\Http\Requests\Users\UserRequest;
use App\Http\Resources\AuditTrail\AuditTrailResource;
use App\Http\Resources\Preferences\UserPreferenceResource;
use App\Http\Resources\Rbac\PermissionResource;
use App\Http\Resources\Users\UserResource;
use App\Models\Preferences\UserPreference;
use App\Models\Users\User;
use App\Queries\Users\UserActivityQuery;
use App\Repositories\Users\interface\IUserRepository;
use App\Traits\HttpUtil;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Inertia\Inertia;
use Spatie\Activitylog\Models\Activity;

class UserController extends ApiDropdownController
{
    use HttpUtil;

    public function __construct(
        protected IUserRepository $repository,
        protected UserActivityQuery $activityQuery,
    ) {}

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
        $permissions->loadMissing('module');

        return PermissionResource::collection($permissions);
    }

    public function getUserActivities(UserActivityIndexRequest $request, User $user): AnonymousResourceCollection
    {
        $this->authorize('view', $user);

        return $this->activityCollection(
            Activity::query()
                ->where('subject_type', $user->getMorphClass())
                ->where('subject_id', $user->getKey()),
            $request,
        );
    }

    public function getMyActivities(UserActivityIndexRequest $request): AnonymousResourceCollection
    {
        $user = $request->user();

        abort_unless($user instanceof User, 401);

        return $this->activityCollection($this->causedByQuery($user), $request);
    }

    public function getUserCausedActivities(UserActivityIndexRequest $request, User $user): AnonymousResourceCollection
    {
        $actor = $request->user();
        abort_unless($actor instanceof User, 401);

        abort_unless(
            $actor->is($user) || $actor->can('root:manage'),
            403
        );

        return $this->activityCollection($this->causedByQuery($user), $request);
    }

    public function activityLookup()
    {
        $actor = request()->user();
        abort_unless($actor instanceof User, 401);
        abort_unless($actor->can('root:manage'), 403);

        $search = trim((string) request()->query('search', ''));

        $query = User::query()
            ->select(['id', 'first_name', 'middle_name', 'last_name', 'email'])
            ->orderBy('first_name')
            ->orderBy('last_name')
            ->limit(25);

        if ($search !== '') {
            $query->where(function ($builder) use ($search): void {
                $builder
                    ->where('first_name', 'like', "%{$search}%")
                    ->orWhere('middle_name', 'like', "%{$search}%")
                    ->orWhere('last_name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            });
        }

        $users = $query->get()->map(fn (User $user): array => [
            'id' => $user->id,
            'name' => $user->full_name,
            'email' => $user->email,
        ]);

        return response()->json(['data' => $users]);
    }

    /**
     * @param  Builder<Activity>  $query
     */
    private function activityCollection(Builder $query, UserActivityIndexRequest $request): AnonymousResourceCollection
    {
        return AuditTrailResource::collection(
            $this->activityQuery->paginate($query->clone(), $request->filters())
        )->additional([
            'log_names' => $this->activityQuery->logNames($query),
        ]);
    }

    /**
     * @return Builder<Activity>
     */
    private function causedByQuery(User $user): Builder
    {
        return Activity::query()
            ->where('causer_type', $user->getMorphClass())
            ->where('causer_id', $user->getKey());
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
