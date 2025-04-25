<?php

namespace App\Repositories\Races\interface;

use App\DTO\Races\RaceDto;
use App\Http\Filters\Shared\SharedTitleFilter;
use App\Models\Races\Race;
use App\Repositories\Base\Interface\IBaseRepository;

interface IRaceRepository extends IBaseRepository
{
	public function create(RaceDto $dto);

	public function update(Race $race, RaceDto $dto);

	public function allFilter($columns = ['*'], SharedTitleFilter $filters = null);
}
