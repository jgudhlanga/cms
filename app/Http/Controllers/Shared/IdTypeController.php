<?php

namespace App\Http\Controllers\Shared;

use App\DTO\Shared\IdTypeDto;
use App\Http\Controllers\Controller;
use App\Http\Filters\Shared\SharedNameFilter;
use App\Http\Requests\Shared\IdTypeRequest;
use App\Http\Resources\Shared\IdTypeResource;
use App\Models\Shared\IdType;
use App\Repositories\Shared\interface\IIdTypeRepository;
use Inertia\Inertia;

class IdTypeController extends Controller
{
	public function __construct(protected IIdTypeRepository $repository)
	{
	}

	public function index(SharedNameFilter $filters)
	{
		$this->authorize('viewSettings');
		$idTypes = IdTypeResource::collection($this->repository->allFilter(['*'], $filters));
		return Inertia::render('shared/idTypes/Index', [
			'idTypes' => $idTypes,
			'filters' => request()->only(['search', 'trashed']),
			'trashedCount' => $this->repository->allTrashed()->count(),
		]);
	}

	public function create()
	{
		$this->authorize('createSettings');
	}

	public function store(IdTypeRequest $request)
	{
		$this->authorize('createSettings');
		$this->repository->create(IdTypeDto::fromIdTypeRequest($request));
	}

	public function show(IdType $idType)
	{
		//
	}

	public function edit(IdType $idType)
	{
		//
	}

	public function update(IdTypeRequest $request, IdType $idType)
	{
		$this->authorize('updateSettings');
		$this->repository->update($idType, IdTypeDto::fromIdTypeRequest($request));
	}

	public function destroy(IdType $idType)
	{
		$this->authorize('deleteSettings');
		$this->repository->delete($idType);
	}

	public function restore(string $id)
	{
		$idType = $this->repository->findTrashed($id);
		$this->authorize('restoreSettings');
		$this->repository->restore($idType);
	}

	public function forceDelete(IdType $idType)
	{
		$this->authorize('forceDeleteSettings');
		$this->repository->delete($idType, true);
	}
}
