<?php

namespace App\Http\Filters\Acl;

use App\Http\Filters\QueryFilter;
use Illuminate\Database\Eloquent\Builder;

class PermissionFilter extends QueryFilter
{
	protected array $sortable = [
		'createdAt' => 'created_at',
		'description',
		'name',
		'guardName' => 'guard_name',
		'updatedAt' => 'updated_at',
	];

	protected array $searchable = ['name', 'description', 'guard_name'];
}

