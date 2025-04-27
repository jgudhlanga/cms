<?php

namespace App\Observers\Shared;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class TitleSlugObserver
{
	public function creating(Model $model): void
	{
		$model->slug = Str::slug($model->title);
	}

	public function updating(Model $model): void
	{
		$model->slug = Str::slug($model->title);
	}
}
