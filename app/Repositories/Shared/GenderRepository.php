<?php

namespace App\Repositories\Shared;


use App\DTO\Shared\GenderDto;
use App\Http\Filters\Shared\SharedTitleFilter;
use App\Models\Shared\Gender;
use App\Repositories\Base\BaseRepository;
use App\Repositories\Shared\interface\IGenderRepository;

class GenderRepository extends BaseRepository implements IGenderRepository
{
	public function __construct(protected Gender $gender)
	{
		parent::__construct($this->gender);
	}

	public function create(GenderDto $dto): Gender
	{
		return $this->gender->create([
			'title' => $dto->title,
			'description' => $dto->description,
		])->refresh();
	}

	public function update(Gender $gender, GenderDto $dto): Gender
	{
		return tap($gender)->update([
			'title' => $dto->title,
			'description' => $dto->description,
		]);
	}

	public function allFilter($columns = ['*'], SharedTitleFilter $filters = null)
	{
		return $this->gender
			->select($columns)
			->filter($filters)
			->orderBy('title')
			->orderBy('deleted_at')
			->paginate()
			->withQueryString();
	}
}
