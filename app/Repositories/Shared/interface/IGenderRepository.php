<?php

namespace App\Repositories\Shared\interface;

use App\DTO\Genders\GenderDto;
use App\Http\Filters\Shared\SharedTitleFilter;
use App\Models\Shared\Gender;
use App\Repositories\Base\Interface\IBaseRepository;

interface IGenderRepository extends IBaseRepository
{
	public function create(GenderDto $dto);

	public function update(Gender $gender, GenderDto $dto);

	public function allFilter($columns = ['*'], SharedTitleFilter $filters = null);
}
