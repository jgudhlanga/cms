<?php

namespace App\Http\Filters\Acl;

use App\Http\Filters\QueryFilter;

class ModuleFilter extends QueryFilter
{
	protected array $sortable = [
		'active' => 'is_active',
		'createdAt' => 'created_at',
		'description',
		'name',
		'title',
		'updatedAt' => 'updated_at',
	];
	protected array $searchable = ['title', 'description'];
}

