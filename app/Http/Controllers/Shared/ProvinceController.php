<?php

namespace App\Http\Controllers\Shared;

use App\DTO\Shared\ProvinceDto;
use App\Http\Controllers\Controller;
use App\Http\Filters\Shared\SharedTitleFilter;
use App\Http\Requests\Shared\ProvinceRequest;
use App\Http\Resources\Shared\ProvinceResource;
use App\Models\Shared\Province;
use App\Repositories\Shared\interface\IProvinceRepository;
use Inertia\Inertia;

class ProvinceController extends Controller
{
	public function __construct(protected IProvinceRepository $repository)
	{
	}

	public function index(SharedTitleFilter $filters)
	{
		$this->authorize('viewAny', Province::class);
		$provinces = ProvinceResource::collection($this->repository->allFilter(['*'], $filters));
		return Inertia::render('shared/provinces/Index', [
			'provinces' => $provinces,
			'filters' => request()->only(['search', 'trashed']),
			'trashedCount' => $this->repository->allTrashed()->count(),
		]);
	}

	public function create()
	{
		$this->authorize('create', Province::class);
	}

	public function store(ProvinceRequest $request)
	{
		$this->authorize('create', Province::class);
		$this->repository->create(ProvinceDto::fromProvinceRequest($request));
	}

	public function show(Province $province)
	{
		//
	}

	public function edit(Province $province)
	{
		//
	}

	public function update(ProvinceRequest $request, Province $province)
	{
		$this->authorize('update', $province);
		$this->repository->update($province, ProvinceDto::fromProvinceRequest($request));
	}

	public function destroy(Province $province)
	{
		$this->authorize('delete', $province);
		$this->repository->delete($province);
	}

	public function restore(string $id)
	{
		$province = $this->repository->findTrashed($id);
		$this->authorize('restore', $province);
		$this->repository->restore($province);
	}

	public function forceDelete(Province $province)
	{
		$this->authorize('forceDelete', $province);
		$this->repository->delete($province, true);
	}
}
