<?php

namespace App\Repositories\Titles\interface;

use App\DTO\Titles\TitleDto;
use App\Http\Filters\Shared\SharedNameFilter;
use App\Models\Titles\Title;
use App\Repositories\Base\Interface\IBaseRepository;

interface ITitleRepository extends IBaseRepository
{
	public function create(TitleDto $dto);

	public function update(Title $title, TitleDto $dto);

	public function allFilter($columns = ['*'], ?SharedNameFilter $filters);
}
