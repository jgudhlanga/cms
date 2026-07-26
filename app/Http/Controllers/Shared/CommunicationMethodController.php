<?php

namespace App\Http\Controllers\Shared;

use App\DTO\Shared\CommunicationMethodDto;
use App\Http\Controllers\Controller;
use App\Http\Filters\Shared\SharedTitleFilter;
use App\Http\Requests\Shared\CommunicationMethodRequest;
use App\Http\Resources\Shared\CommunicationMethodResource;
use App\Models\Shared\CommunicationMethod;
use App\Repositories\Shared\interface\ICommunicationMethodRepository;
use Inertia\Inertia;

class CommunicationMethodController extends Controller
{
	public function __construct(protected ICommunicationMethodRepository $repository)
	{
	}

	public function index(SharedTitleFilter $filters)
	{
		$this->authorize('viewAny', CommunicationMethod::class);
		$methods = CommunicationMethodResource::collection($this->repository->allFilter(['*'], $filters));
		return Inertia::render('shared/communications/methods/Index', [
			'methods' => $methods,
			'filters' => request()->only(['search', 'trashed']),
			'trashedCount' => $this->repository->allTrashed()->count(),
		]);
	}

	public function create()
	{
		$this->authorize('create', CommunicationMethod::class);
	}

	public function store(CommunicationMethodRequest $request)
	{
		$this->authorize('create', CommunicationMethod::class);
		$this->repository->create(CommunicationMethodDto::fromCommunicationMethodRequest($request));
	}

	public function show(CommunicationMethod $communicationMethod)
	{
		//
	}

	public function edit(CommunicationMethod $communicationMethod)
	{
		//
	}

	public function update(CommunicationMethodRequest $request, CommunicationMethod $communicationMethod)
	{
		$this->authorize('update', $communicationMethod);
		$this->repository->update($communicationMethod, CommunicationMethodDto::fromCommunicationMethodRequest($request));
	}

	public function destroy(CommunicationMethod $communicationMethod)
	{
		$this->authorize('delete', $communicationMethod);
		$this->repository->delete($communicationMethod);
	}

	public function restore(string $id)
	{
		$communicationMethod = $this->repository->findTrashed($id);
		$this->authorize('restore', $communicationMethod);
		$this->repository->restore($communicationMethod);
	}

	public function forceDelete(CommunicationMethod $communicationMethod)
	{
		$this->authorize('forceDelete', $communicationMethod);
		$this->repository->delete($communicationMethod, true);
	}
}
