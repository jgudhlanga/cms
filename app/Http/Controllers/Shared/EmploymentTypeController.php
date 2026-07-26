<?php

namespace App\Http\Controllers\Shared;

use App\DTO\Shared\EmploymentTypeDto;
use App\Http\Controllers\Controller;
use App\Http\Filters\Shared\SharedNameFilter;
use App\Http\Requests\Shared\EmploymentTypeRequest;
use App\Http\Resources\Shared\EmploymentTypeResource;
use App\Models\Shared\EmploymentType;
use App\Repositories\Shared\interface\IEmploymentTypeRepository;
use Inertia\Inertia;

class EmploymentTypeController extends Controller
{
	public function __construct(protected IEmploymentTypeRepository $repository)
	{
	}

	public function index(SharedNameFilter $filters)
	{
		$this->authorize('viewAny', EmploymentType::class);
		$employmentTypes = EmploymentTypeResource::collection($this->repository->allFilter(['*'], $filters));
		return Inertia::render('shared/employmentTypes/Index', [
			'employmentTypes' => $employmentTypes,
			'filters' => request()->only(['search', 'trashed']),
			'trashedCount' => $this->repository->allTrashed()->count(),
		]);
	}

	public function create()
	{
		$this->authorize('create', EmploymentType::class);
	}

	public function store(EmploymentTypeRequest $request)
	{
		$this->authorize('create', EmploymentType::class);
		$this->repository->create(EmploymentTypeDto::fromEmploymentTypeRequest($request));
	}

	public function show(EmploymentType $employmentType)
	{
		//
	}

	public function edit(EmploymentType $employmentType)
	{
		//
	}

	public function update(EmploymentTypeRequest $request, EmploymentType $employmentType)
	{
		$this->authorize('update', $employmentType);
		$this->repository->update($employmentType, EmploymentTypeDto::fromEmploymentTypeRequest($request));
	}

	public function destroy(EmploymentType $employmentType)
	{
		$this->authorize('delete', $employmentType);
		$this->repository->delete($employmentType);
	}

	public function restore(string $id)
	{
		$employmentType = $this->repository->findTrashed($id);
		$this->authorize('restore', $employmentType);
		$this->repository->restore($employmentType);
	}

	public function forceDelete(EmploymentType $employmentType)
	{
		$this->authorize('forceDelete', $employmentType);
		$this->repository->delete($employmentType, true);
	}
}
