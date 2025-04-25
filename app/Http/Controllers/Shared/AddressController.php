<?php

namespace App\Http\Controllers\Shared;

use App\DTO\Shared\AddressDto;
use App\Http\Controllers\Controller;
use App\Http\Requests\Shared\AddressRequest;
use App\Models\Shared\Address;
use App\Repositories\Shared\interface\IAddressRepository;

class AddressController extends Controller
{
	public function __construct(protected IAddressRepository $repository)
	{
	}


	public function update(AddressRequest $request, Address $address)
	{
		$this->authorize('create', $address);
		$this->repository->update($address, AddressDto::fromAddressRequest($request));
	}

	public function destroy(Address $address)
	{
		$this->authorize('delete', $address);
		$this->repository->delete($address);
	}

	public function restore(string $id)
	{
		$address = $this->repository->findTrashed($id);
		$this->authorize('restore', $address);
		$this->repository->restore($address);
	}

	public function forceDelete(Address $address)
	{
		$this->authorize('forceDelete', $address);
		$this->repository->delete($address, true);
	}
}
