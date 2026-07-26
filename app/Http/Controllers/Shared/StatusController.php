<?php

namespace App\Http\Controllers\Shared;

use App\DTO\Shared\StatusDto;
use App\Http\Controllers\Controller;
use App\Http\Filters\Shared\SharedTitleFilter;
use App\Http\Requests\Shared\StatusRequest;
use App\Http\Resources\Shared\StatusResource;
use App\Models\Shared\Status;
use App\Repositories\Shared\interface\IStatusRepository;
use Inertia\Inertia;

class StatusController extends Controller
{
	public function __construct(protected IStatusRepository $repository)
	{
	}

	public function index(SharedTitleFilter $filters)
	{
		$this->authorize('viewAny', Status::class);
		$statuses = StatusResource::collection($this->repository->allFilter(['*'], $filters));
		return Inertia::render('shared/statuses/Index', [
			'statuses' => $statuses,
			'filters' => request()->only(['search', 'trashed']),
			'trashedCount' => $this->repository->allTrashed()->count(),
		]);
	}

	public function create()
	{
		$this->authorize('create', Status::class);
	}

	public function store(StatusRequest $request)
	{
		$this->authorize('create', Status::class);
		$this->repository->create(StatusDto::fromStatusRequest($request));
	}

	public function show(Status $status)
	{
		//
	}

	public function edit(Status $status)
	{
		//
	}

	public function update(StatusRequest $request, Status $status)
	{
		$this->authorize('update', $status);
		$this->repository->update($status, StatusDto::fromStatusRequest($request));
	}

	public function destroy(Status $status)
	{
		$this->authorize('delete', $status);
		$this->repository->delete($status);
	}

	public function restore(string $id)
	{
		$status = $this->repository->findTrashed($id);
		$this->authorize('restore', $status);
		$this->repository->restore($status);
	}

	public function forceDelete(Status $status)
	{
		$this->authorize('forceDelete', $status);
		$this->repository->delete($status, true);
	}
}
