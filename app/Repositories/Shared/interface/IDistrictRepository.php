<?php

namespace App\Repositories\Shared\interface;

use App\DTO\Shared\DistrictDto;
use App\Http\Filters\Shared\SharedNameFilter;
use App\Models\Shared\District;
use App\Repositories\Base\Interface\IBaseRepository;

interface IDistrictRepository extends IBaseRepository
{
	public function create(DistrictDto $dto);

	public function update(District $district, DistrictDto $dto);

	public function allFilter($columns = ['*'], SharedNameFilter $filters = null);
}
