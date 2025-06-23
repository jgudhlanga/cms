<?php

namespace App\Repositories\Shared;


use App\DTO\Shared\RaceDto;
use App\Http\Filters\Shared\SharedTitleFilter;
use App\Models\Shared\Race;
use App\Repositories\Base\BaseRepository;
use App\Repositories\Shared\interface\IRaceRepository;

class RaceRepository extends BaseRepository implements IRaceRepository
{
	public function __construct(protected Race $race)
	{
		parent::__construct($this->race);
	}

	public function create(RaceDto $dto): Race
	{
		return $this->race->create([
			'title' => $dto->title,
			'description' => $dto->description,
		])->refresh();
	}

	public function update(Race $race, RaceDto $dto): Race
	{
		return tap($race)->update([
			'title' => $dto->title,
			'description' => $dto->description,
		]);
	}

	public function allFilter($columns = ['*'], SharedTitleFilter $filters = null)
	{
		return $this->race
			->select($columns)
			->filter($filters)
			->orderBy('title')
			->orderBy('deleted_at')
			->paginate()
			->withQueryString();
	}
}
