<?php

namespace App\Http\Controllers\Shared;

use App\DTO\Shared\ReligionDto;
use App\Http\Controllers\Controller;
use App\Http\Filters\Shared\SharedNameFilter;
use App\Http\Requests\Shared\ReligionRequest;
use App\Http\Resources\Shared\ReligionResource;
use App\Models\Shared\Religion;
use App\Repositories\Shared\interface\IReligionRepository;
use Inertia\Inertia;

class ReligionController extends Controller
{
	public function __construct(protected IReligionRepository $repository)
	{
	}

	public function index(SharedNameFilter $filters)
	{
		$this->authorize('viewAny', Religion::class);
		$religions = ReligionResource::collection($this->repository->allFilter(['*'], $filters));
		return Inertia::render('shared/religions/Index', [
			'religions' => $religions,
			'filters' => request()->only(['search', 'trashed']),
			'trashedCount' => $this->repository->allTrashed()->count(),
		]);
	}

	public function create()
	{
		$this->authorize('create', Religion::class);
	}

	public function store(ReligionRequest $request)
	{
		$this->authorize('create', Religion::class);
		$this->repository->create(ReligionDto::fromReligionRequest($request));
	}

	public function show(Religion $religion)
	{
		//
	}

	public function edit(Religion $religion)
	{
		//
	}

	public function update(ReligionRequest $request, Religion $religion)
	{
		$this->authorize('update', $religion);
		$this->repository->update($religion, ReligionDto::fromReligionRequest($request));
	}

	public function destroy(Religion $religion)
	{
		$this->authorize('delete', $religion);
		$this->repository->delete($religion);
	}

	public function restore(string $id)
	{
		$religion = $this->repository->findTrashed($id);
		$this->authorize('restore', $religion);
		$this->repository->restore($religion);
	}

	public function forceDelete(Religion $religion)
	{
		$this->authorize('forceDelete', $religion);
		$this->repository->delete($religion, true);
	}
}
