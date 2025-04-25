<?php

namespace App\Http\Controllers\AddressTypes;

use App\Http\Controllers\Controller;
use App\Http\Filters\Shared\SharedTitleFilter;
use App\Http\Requests\AddressTypes\AddressTypeRequest;
use App\Http\Resources\AddressTypes\AddressTypeResource;
use App\Repositories\AddressTypes\interface\IAddressTypeRepository;
use App\DTO\AddressTypes\AddressTypeDto;
use App\Models\AddressTypes\AddressType;
use Inertia\Inertia;

class AddressTypeController extends Controller
{
    public function __construct(protected IAddressTypeRepository $repository)
	{
	}


	public function index(SharedTitleFilter $filters)
	{
		$this->authorize('viewSettings');
		$addressTypes = AddressTypeResource::collection($this->repository->allFilter(['*'], $filters));
		return Inertia::render('addressTypes/Index', [
			'addressTypes' => $addressTypes,
			'filters' => request()->only(['search', 'trashed']),
			'trashedCount' => $this->repository->allTrashed()->count(),
		]);
	}

	public function create()
	{
		$this->authorize('createSettings');
	}

	public function store(AddressTypeRequest $request)
	{
		$this->authorize('createSettings');
		$this->repository->create(AddressTypeDto::fromAddressTypeRequest($request));
	}

	public function show(AddressType $addressType)
	{
		//
	}

	public function edit(AddressType $addressType)
	{
		//
	}

	public function update(AddressTypeRequest $request, AddressType $addressType)
	{
		$this->authorize('updateSettings');
		$this->repository->update($addressType, AddressTypeDto::fromAddressTypeRequest($request));
	}

	public function destroy(AddressType $addressType)
	{
		$this->authorize('deleteSettings');
		$this->repository->delete($addressType);
	}

	public function restore(string $id)
	{
		$addressType = $this->repository->findTrashed($id);
		$this->authorize('restoreSettings');
		$this->repository->restore($addressType);
	}

	public function forceDelete(AddressType $addressType)
	{
		$this->authorize('forceDeleteSettings');
		$this->repository->delete($addressType, true);
	}
}
