<?php

namespace App\Http\Controllers\Shared;

use App\DTO\Shared\GenderDto;
use App\Http\Controllers\Controller;
use App\Http\Filters\Shared\SharedTitleFilter;
use App\Http\Requests\Shared\GenderRequest;
use App\Http\Resources\Shared\GenderResource;
use App\Models\Shared\Gender;
use App\Repositories\Shared\interface\IGenderRepository;
use Inertia\Inertia;

class GenderController extends Controller
{
	public function __construct(protected IGenderRepository $repository)
	{
	}

	public function index(SharedTitleFilter $filters)
	{
		$this->authorize('viewSettings');
		$genders = GenderResource::collection($this->repository->allFilter(['*'], $filters));
		return Inertia::render('shared/genders/Index', [
			'genders' => $genders,
			'filters' => request()->only(['search', 'trashed']),
			'trashedCount' => $this->repository->allTrashed()->count(),
		]);
	}

	public function create()
	{
		$this->authorize('createSettings');
	}

	public function store(GenderRequest $request)
	{
		$this->authorize('createSettings');
		$this->repository->create(GenderDto::fromGenderRequest($request));
	}

	public function show(Gender $gender)
	{
		//
	}

	public function edit(Gender $gender)
	{
		//
	}

	public function update(GenderRequest $request, Gender $gender)
	{
		$this->authorize('updateSettings');
		$this->repository->update($gender, GenderDto::fromGenderRequest($request));
	}

	public function destroy(Gender $gender)
	{
		$this->authorize('deleteSettings');
		$this->repository->delete($gender);
	}

	public function restore(string $id)
	{
		$gender = $this->repository->findTrashed($id);
		$this->authorize('restoreSettings');
		$this->repository->restore($gender);
	}

	public function forceDelete(Gender $gender)
	{
		$this->authorize('forceDeleteSettings');
		$this->repository->delete($gender, true);
	}
}
