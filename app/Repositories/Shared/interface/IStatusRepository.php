<?php

namespace App\Repositories\Shared\interface;

use App\DTO\Shared\StatusDto;
use App\Http\Filters\Shared\SharedTitleFilter;
use App\Models\Shared\Status;
use App\Repositories\Base\Interface\IBaseRepository;

interface IStatusRepository extends IBaseRepository
{
	public function create(StatusDto $dto);

	public function update(Status $status, StatusDto $dto);

	public function allFilter($columns = ['*'], SharedTitleFilter $filters = null);
}
