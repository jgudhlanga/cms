<?php

namespace App\Repositories\Shared;


use App\DTO\Provinces\ProvinceDto;
use App\Http\Filters\Shared\SharedTitleFilter;
use App\Models\Shared\Province;
use App\Repositories\Base\BaseRepository;
use App\Repositories\Shared\interface\IProvinceRepository;

class ProvinceRepository extends BaseRepository implements IProvinceRepository
{
	public function __construct(protected Province $province)
	{
		parent::__construct($this->province);
	}

	public function create(ProvinceDto $dto): Province
	{
		return $this->province->create([
			'title' => $dto->title,
			'description' => $dto->description,
		])->refresh();
	}

	public function update(Province $province, ProvinceDto $dto): Province
	{
		return tap($province)->update([
			'title' => $dto->title,
			'description' => $dto->description,
		]);
	}

	public function allFilter($columns = ['*'], SharedTitleFilter $filters = null)
	{
		return $this->province
			->select($columns)
			->filter($filters)
			->orderBy('title')
			->orderBy('deleted_at')
			->paginate()
			->withQueryString();
	}
}
