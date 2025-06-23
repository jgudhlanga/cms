<?php

namespace App\Repositories\Shared;


use App\DTO\AddressTypes\AddressTypeDto;
use App\Http\Filters\Shared\SharedTitleFilter;
use App\Models\Shared\AddressType;
use App\Repositories\Base\BaseRepository;
use App\Repositories\Shared\interface\IAddressTypeRepository;

class AddressTypeRepository extends BaseRepository implements IAddressTypeRepository
{
	public function __construct(protected AddressType $addressType)
	{
		parent::__construct($this->addressType);
	}

	public function create(AddressTypeDto $dto): AddressType
	{
		return $this->addressType->create([
			'title' => $dto->title,
			'description' => $dto->description,
		])->refresh();
	}

	public function update(AddressType $addressType, AddressTypeDto $dto): AddressType
	{
		return tap($addressType)->update([
			'title' => $dto->title,
			'description' => $dto->description,
		]);
	}

	public function allFilter($columns = ['*'], ?SharedTitleFilter $filters = null)
	{
		return $this->addressType
			->select($columns)
			->filter($filters)
			->orderBy('title')
			->orderBy('deleted_at')
			->paginate()
			->withQueryString();
	}
}
