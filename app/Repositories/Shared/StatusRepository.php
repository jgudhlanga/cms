<?php

namespace App\Repositories\Shared;


use App\DTO\Shared\StatusDto;
use App\Http\Filters\Shared\SharedTitleFilter;
use App\Models\Shared\Status;
use App\Repositories\Base\BaseRepository;
use App\Repositories\Shared\interface\IStatusRepository;

class StatusRepository extends BaseRepository implements IStatusRepository
{
	public function __construct(protected Status $status)
	{
		parent::__construct($this->status);
	}

	public function create(StatusDto $dto): Status
	{
		return $this->status->create([
			'title' => $dto->title,
			'description' => $dto->description,
		])->refresh();
	}

	public function update(Status $status, StatusDto $dto): Status
	{
		return tap($status)->update([
			'title' => $dto->title,
			'description' => $dto->description,
		]);
	}

	public function allFilter($columns = ['*'], SharedTitleFilter $filters = null)
	{
		return $this->status
			->select($columns)
			->filter($filters)
			->orderBy('title')
			->orderBy('deleted_at')
			->paginate()
			->withQueryString();
	}
}
