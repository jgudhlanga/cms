<?php

namespace App\Http\Filters\Acl;

use App\Http\Filters\QueryFilter;
use Illuminate\Database\Eloquent\Builder;

class RoleFilter extends QueryFilter
{
    protected array $sortable = [
        'createdAt' => 'created_at',
        'description',
        'name',
        'guardName' => 'guard_name',
        'updatedAt' => 'updated_at',
    ];

    protected array $searchable = ['name', 'description', 'slug'];

    protected array $excludes = ['exclude'];
    protected array $only = ['only'];

    public function exclude($value): Builder
    {
        $exclude = $value;
        if (is_string($exclude)) {
            $exclude = explode(',', $exclude);
        }
        return $this->builder->when(!empty($exclude), fn($query) => $query->whereNotIn('slug', $exclude));
    }

    public function only($value): Builder
    {
        $only = $value;
        if (is_string($only)) {
            $only = explode(',', $only);
        }
        return $this->builder->when(!empty($only), fn($query) => $query->whereIn('slug', $only));
    }
}

