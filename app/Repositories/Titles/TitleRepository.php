<?php

namespace App\Repositories\Titles;


use App\DTO\Titles\TitleDto;
use App\Http\Filters\Shared\SharedNameFilter;
use App\Models\Titles\Title;
use App\Repositories\Base\BaseRepository;
use App\Repositories\Titles\interface\ITitleRepository;

class TitleRepository extends BaseRepository implements ITitleRepository
{
	public function __construct(protected Title $title)
	{
		parent::__construct($this->title);
	}

	public function create(TitleDto $dto): Title
	{
		return $this->title->create([
			'name' => $dto->name,
			'description' => $dto->description,
		])->refresh();
	}

	public function update(Title $title, TitleDto $dto): Title
	{
		return tap($title)->update([
			'name' => $dto->name,
			'description' => $dto->description,
		]);
	}

	public function allFilter($columns = ['*'], ?SharedNameFilter $filters)
	{
		return $this->title
			->select($columns)
			->filter($filters)
			->orderBy('name')
			->orderBy('deleted_at')
			->paginate()
			->withQueryString();
	}
}
