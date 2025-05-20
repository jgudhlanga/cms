<?php

namespace App\Http\Controllers\Users;

use App\DTO\Users\UserDto;
use App\Http\Controllers\Controller;
use App\Http\Filters\Users\UserFilter;
use App\Http\Requests\Users\CreateUserRequest;
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

	public function store(CreateUserRequest $request)
	{
		$this->authorize('create', User::class);
        $tenant = $request->user()->tenant;
		$this->repository->create(UserDto::fromCreateUserRequest($request, $tenant));
	}

	public function show(User $user)
	{
		$this->authorize('view', $user);

		return Inertia::render('users/Show', [
			'user' => new UserResource($user),
			'filters' => request()->only(['search', 'trashed']),
		]);
	}

	public function edit(User $user)
	{
		//
	}

	public function update(UpdateUserRequest $request, User $user)
	{
		$this->authorize('update', $user);
		$this->repository->update($user, UserDto::fromUpdateUseRequest($request));
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
