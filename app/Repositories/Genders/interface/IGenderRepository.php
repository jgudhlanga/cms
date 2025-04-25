<?php

namespace App\Repositories\Genders\interface;

use App\DTO\Genders\GenderDto;
use App\Http\Filters\Shared\SharedTitleFilter;
use App\Models\Genders\Gender;
use App\Repositories\Base\Interface\IBaseRepository;

interface IGenderRepository extends IBaseRepository
{
	public function create(GenderDto $dto);

	public function update(Gender $gender, GenderDto $dto);

	public function allFilter($columns = ['*'], SharedTitleFilter $filters = null);
}
