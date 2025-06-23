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
		$this->authorize('viewSettings');
		$statuses = StatusResource::collection($this->repository->allFilter(['*'], $filters));
		return Inertia::render('shared/statuses/Index', [
			'statuses' => $statuses,
			'filters' => request()->only(['search', 'trashed']),
			'trashedCount' => $this->repository->allTrashed()->count(),
		]);
	}

	public function create()
	{
		$this->authorize('createSettings');
	}

	public function store(StatusRequest $request)
	{
		$this->authorize('createSettings');
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
		$this->authorize('updateSettings');
		$this->repository->update($status, StatusDto::fromStatusRequest($request));
	}

	public function destroy(Status $status)
	{
		$this->authorize('deleteSettings');
		$this->repository->delete($status);
	}

	public function restore(string $id)
	{
		$status = $this->repository->findTrashed($id);
		$this->authorize('restoreSettings');
		$this->repository->restore($status);
	}

	public function forceDelete(Status $status)
	{
		$this->authorize('forceDeleteSettings');
		$this->repository->delete($status, true);
	}
}
