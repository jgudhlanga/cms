<?php

namespace App\Http\Controllers\Shared;

use App\DTO\Shared\RaceDto;
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
		$this->authorize('viewAny', Race::class);
		$races = RaceResource::collection($this->repository->allFilter(['*'], $filters));
		return Inertia::render('shared/races/Index', [
			'races' => $races,
			'filters' => request()->only(['search', 'trashed']),
			'trashedCount' => $this->repository->allTrashed()->count(),
		]);
	}

	public function create()
	{
		$this->authorize('create', Race::class);
	}

	public function store(RaceRequest $request)
	{
		$this->authorize('create', Race::class);
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
		$this->authorize('update', $race);
		$this->repository->update($race, RaceDto::fromRaceRequest($request));
	}

	public function destroy(Race $race)
	{
		$this->authorize('delete', $race);
		$this->repository->delete($race);
	}

	public function restore(string $id)
	{
		$race = $this->repository->findTrashed($id);
		$this->authorize('restore', $race);
		$this->repository->restore($race);
	}

	public function forceDelete(Race $race)
	{
		$this->authorize('forceDelete', $race);
		$this->repository->delete($race, true);
	}
}
