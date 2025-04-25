<?php

namespace App\Repositories\Shared\interface;

use App\DTO\Shared\AddressDto;
use App\Models\Shared\Address;
use App\Repositories\Base\Interface\IBaseRepository;
use Illuminate\Database\Eloquent\Model;

interface IAddressRepository extends IBaseRepository
{
	public function create(Model $model, AddressDto $dto);

	public function update(Address $address, AddressDto $dto);

}
