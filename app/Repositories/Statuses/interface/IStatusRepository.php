<?php

namespace App\Repositories\Statuses\interface;

use App\DTO\Statuses\StatusDto;
use App\Http\Filters\Shared\SharedTitleFilter;
use App\Models\Statuses\Status;
use App\Repositories\Base\Interface\IBaseRepository;

interface IStatusRepository extends IBaseRepository
{
	public function create(StatusDto $dto);

	public function update(Status $status, StatusDto $dto);

	public function allFilter($columns = ['*'], SharedTitleFilter $filters = null);
}
