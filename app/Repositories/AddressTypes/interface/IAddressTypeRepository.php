<?php

namespace App\Repositories\AddressTypes\interface;

use App\DTO\AddressTypes\AddressTypeDto;
use App\Http\Filters\Shared\SharedTitleFilter;
use App\Models\AddressTypes\AddressType;
use App\Repositories\Base\Interface\IBaseRepository;

interface IAddressTypeRepository extends IBaseRepository
{
	public function create(AddressTypeDto $dto);

	public function update(AddressType $addressType, AddressTypeDto $dto);

	public function allFilter($columns = ['*'], ?SharedTitleFilter $filters = null);
}
