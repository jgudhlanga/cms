<?php

namespace App\Repositories\Shared\interface;

use App\DTO\Shared\AddressTypeDto;
use App\Http\Filters\Shared\SharedTitleFilter;
use App\Models\Shared\AddressType;
use App\Repositories\Base\Interface\IBaseRepository;

interface IAddressTypeRepository extends IBaseRepository
{
	public function create(AddressTypeDto $dto);

	public function update(AddressType $addressType, AddressTypeDto $dto);

	public function allFilter($columns = ['*'], ?SharedTitleFilter $filters = null);
}
