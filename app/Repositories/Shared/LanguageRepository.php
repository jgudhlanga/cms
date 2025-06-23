<?php

namespace App\Repositories\Shared;


use App\DTO\Languages\LanguageDto;
use App\Http\Filters\Shared\SharedTitleFilter;
use App\Models\Shared\Language;
use App\Repositories\Base\BaseRepository;
use App\Repositories\Shared\interface\ILanguageRepository;

class LanguageRepository extends BaseRepository implements ILanguageRepository
{
	public function __construct(protected Language $language)
	{
		parent::__construct($this->language);
	}

	public function create(LanguageDto $dto): Language
	{
		return $this->language->create([
			'title' => $dto->title,
			'description' => $dto->description,
		])->refresh();
	}

	public function update(Language $language, LanguageDto $dto): Language
	{
		return tap($language)->update([
			'title' => $dto->title,
			'description' => $dto->description,
		]);
	}

	public function allFilter($columns = ['*'], SharedTitleFilter $filters = null)
	{
		return $this->language
			->select($columns)
			->filter($filters)
			->orderBy('title')
			->orderBy('deleted_at')
			->paginate()
			->withQueryString();
	}
}
