<?php

namespace App\Repositories\Provinces\interface;

use App\DTO\Provinces\ProvinceDto;
use App\Http\Filters\Shared\SharedTitleFilter;
use App\Models\Provinces\Province;
use App\Repositories\Base\Interface\IBaseRepository;

interface IProvinceRepository extends IBaseRepository
{
	public function create(ProvinceDto $dto);

	public function update(Province $province, ProvinceDto $dto);

	public function allFilter($columns = ['*'], SharedTitleFilter $filters = null);
}
