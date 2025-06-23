<?php

namespace App\Http\Controllers\Shared;

use App\DTO\Communications\CommunicationMethodDto;
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
		$this->authorize('viewSettings');
		$methods = CommunicationMethodResource::collection($this->repository->allFilter(['*'], $filters));
		return Inertia::render('shared/communications/methods/Index', [
			'methods' => $methods,
			'filters' => request()->only(['search', 'trashed']),
			'trashedCount' => $this->repository->allTrashed()->count(),
		]);
	}

	public function create()
	{
		$this->authorize('createSettings');
	}

	public function store(CommunicationMethodRequest $request)
	{
		$this->authorize('createSettings');
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
		$this->authorize('updateSettings');
		$this->repository->update($communicationMethod, CommunicationMethodDto::fromCommunicationMethodRequest($request));
	}

	public function destroy(CommunicationMethod $communicationMethod)
	{
		$this->authorize('deleteSettings');
		$this->repository->delete($communicationMethod);
	}

	public function restore(string $id)
	{
		$model = $this->repository->findTrashed($id);
		$this->authorize('restoreSettings');
		$this->repository->restore($model);
	}

	public function forceDelete(CommunicationMethod $communicationMethod)
	{
		$this->authorize('forceDeleteSettings');
		$this->repository->delete($communicationMethod, true);
	}
}
