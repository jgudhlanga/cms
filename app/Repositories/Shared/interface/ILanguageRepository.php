<?php

namespace App\Repositories\Shared\interface;

use App\DTO\Languages\LanguageDto;
use App\Http\Filters\Shared\SharedTitleFilter;
use App\Models\Shared\Language;
use App\Repositories\Base\Interface\IBaseRepository;

interface ILanguageRepository extends IBaseRepository
{
	public function create(LanguageDto $dto);

	public function update(Language $language, LanguageDto $dto);

	public function allFilter($columns = ['*'], SharedTitleFilter $filters = null);
}
