<?php

namespace App\Http\Controllers\Religions;

use App\DTO\Religions\ReligionDto;
use App\Http\Controllers\Controller;
use App\Http\Filters\Shared\SharedNameFilter;
use App\Http\Requests\Religions\ReligionRequest;
use App\Http\Resources\Religions\ReligionResource;
use App\Models\Religions\Religion;
use App\Repositories\Religions\interface\IReligionRepository;
use Inertia\Inertia;

class ReligionController extends Controller
{
	public function __construct(protected IReligionRepository $repository)
	{
	}

	public function index(SharedNameFilter $filters)
	{
		$this->authorize('viewSettings');
		$religions = ReligionResource::collection($this->repository->allFilter(['*'], $filters));
		return Inertia::render('religions/Index', [
			'religions' => $religions,
			'filters' => request()->only(['search', 'trashed']),
			'trashedCount' => $this->repository->allTrashed()->count(),
		]);
	}

	public function create()
	{
		$this->authorize('createSettings');
	}

	public function store(ReligionRequest $request)
	{
		$this->authorize('createSettings');
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
		$this->authorize('updateSettings');
		$this->repository->update($religion, ReligionDto::fromReligionRequest($request));
	}

	public function destroy(Religion $religion)
	{
		$this->authorize('deleteSettings');
		$this->repository->delete($religion);
	}

	public function restore(string $id)
	{
		$model = $this->repository->findTrashed($id);
		$this->authorize('restoreSettings');
		$this->repository->restore($model);
	}

	public function forceDelete(Religion $religion)
	{
		$this->authorize('forceDeleteSettings');
		$this->repository->delete($religion, true);
	}
}
