<?php

namespace App\Repositories\Shared\interface;

use App\DTO\Shared\RaceDto;
use App\Http\Filters\Shared\SharedTitleFilter;
use App\Models\Shared\Race;
use App\Repositories\Base\Interface\IBaseRepository;

interface IRaceRepository extends IBaseRepository
{
	public function create(RaceDto $dto);

	public function update(Race $race, RaceDto $dto);

	public function allFilter($columns = ['*'], SharedTitleFilter $filters = null);
}
