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
		$this->authorize('viewSettings');
		$provinces = ProvinceResource::collection($this->repository->allFilter(['*'], $filters));
		return Inertia::render('shared/provinces/Index', [
			'provinces' => $provinces,
			'filters' => request()->only(['search', 'trashed']),
			'trashedCount' => $this->repository->allTrashed()->count(),
		]);
	}

	public function create()
	{
		$this->authorize('createSettings');
	}

	public function store(ProvinceRequest $request)
	{
		$this->authorize('createSettings');
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
		$this->authorize('updateSettings');
		$this->repository->update($province, ProvinceDto::fromProvinceRequest($request));
	}

	public function destroy(Province $province)
	{
		$this->authorize('deleteSettings');
		$this->repository->delete($province);
	}

	public function restore(string $id)
	{
		$province = $this->repository->findTrashed($id);
		$this->authorize('restoreSettings');
		$this->repository->restore($province);
	}

	public function forceDelete(Province $province)
	{
		$this->authorize('forceDeleteSettings');
		$this->repository->delete($province, true);
	}
}
