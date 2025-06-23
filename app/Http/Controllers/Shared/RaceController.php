<?php

namespace App\Http\Controllers\Shared;

use App\DTO\Races\RaceDto;
use App\Http\Controllers\Controller;
use App\Http\Filters\Shared\SharedTitleFilter;
use App\Http\Requests\Shared\RaceRequest;
use App\Http\Resources\Shared\RaceResource;
use App\Models\Shared\Race;
use App\Repositories\Shared\interface\IRaceRepository;
use Inertia\Inertia;

class RaceController extends Controller
{
	public function __construct(protected IRaceRepository $repository)
	{
	}

	public function index(SharedTitleFilter $filters)
	{
		$this->authorize('viewSettings');
		$races = RaceResource::collection($this->repository->allFilter(['*'], $filters));
		return Inertia::render('races/Index', [
			'races' => $races,
			'filters' => request()->only(['search', 'trashed']),
			'trashedCount' => $this->repository->allTrashed()->count(),
		]);
	}

	public function create()
	{
		$this->authorize('createSettings');
	}

	public function store(RaceRequest $request)
	{
		$this->authorize('createSettings');
		$this->repository->create(RaceDto::fromRaceRequest($request));
	}

	public function show(Race $race)
	{
		//
	}

	public function edit(Race $race)
	{
		//
	}

	public function update(RaceRequest $request, Race $race)
	{
		$this->authorize('updateSettings');
		$this->repository->update($race, RaceDto::fromRaceRequest($request));
	}

	public function destroy(Race $race)
	{
		$this->authorize('deleteSettings');
		$this->repository->delete($race);
	}

	public function restore(string $id)
	{
		$race = $this->repository->findTrashed($id);
		$this->authorize('restoreSettings');
		$this->repository->restore($race);
	}

	public function forceDelete(Race $race)
	{
		$this->authorize('forceDeleteSettings');
		$this->repository->delete($race, true);
	}
}
