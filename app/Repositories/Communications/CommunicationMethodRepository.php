<?php

namespace App\Repositories\Communications;

use App\DTO\Communications\CommunicationMethodDto;
use App\Http\Filters\Shared\SharedTitleFilter;
use App\Models\Communications\CommunicationMethod;
use App\Repositories\Base\BaseRepository;
use App\Repositories\Communications\interface\ICommunicationMethodRepository;

class CommunicationMethodRepository extends BaseRepository implements ICommunicationMethodRepository
{
	public function __construct(protected CommunicationMethod $communicationMethod)
	{
		parent::__construct($this->communicationMethod);
	}

	public function create(CommunicationMethodDto $dto): CommunicationMethod
	{
		return $this->communicationMethod->create([
			'title' => $dto->title,
		])->refresh();
	}

	public function update(CommunicationMethod $communicationMethod, CommunicationMethodDto $dto): CommunicationMethod
	{
		return tap($communicationMethod)->update([
			'title' => $dto->title,
		]);
	}

	public function allFilter($columns = ['*'], SharedTitleFilter $filters = null)
	{
		return $this->communicationMethod
			->select($columns)
			->filter($filters)
			->orderBy('title')
			->orderBy('deleted_at')
			->paginate()
			->withQueryString();
	}
}
