<?php

namespace App\Http\Filters;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

abstract class QueryFilter
{
    protected Request $request;
    protected Builder $builder;
    protected array $routeModels = [];
    protected array $sortable = [];
    protected array $searchable = [];

    protected array $joins = [];
    protected array $excludes = [];
    protected array $only = [];

    public function __construct(Request $request)
    {
        $this->request = $request;
    }

    public function apply(Builder $builder): Builder
    {
        $this->builder = $builder;
        $this->queryFilters();
        $this->queryJoins();
        $this->queryRouteModel();
        $this->queryExcludes();
        $this->queryOnly();
        return $this->builder;
    }

    public function trashed($value): Builder
    {
        if ($value == '1') {
            return $this->builder->withTrashed();
        } else if ($value == '2') {
            return $this->builder->onlyTrashed();
        } else {
            return $this->builder->withoutTrashed();
        }
    }

    protected function filters(): array
    {
        return $this->request->all();
    }

    protected function routeModelParams(): array
    {
        return $this->routeModels;
    }

    public function sort($value): void
    {
        $sortAttributes = explode(',', $value);

        foreach ($sortAttributes as $sortAttribute) {
            $direction = 'asc';
            if (str_starts_with($sortAttribute, '-')) {
                $direction = 'desc';
                $sortAttribute = ltrim($sortAttribute, '-');
            }

            if (!in_array($sortAttribute, $this->sortable) && !array_key_exists($sortAttribute, $this->sortable)) {
                continue;
            }
            $columnName = $this->sortable[$sortAttribute] ?? null;
            if ($columnName === null) {
                $columnName = $sortAttribute;
            }
            $this->builder->orderBy($columnName, $direction);
        }
    }

    public function search($value): void
    {
        $trashed = request('trashed');

        $this->builder->when($value, function ($query) use ($value, $trashed) {
            foreach ($this->searchable as $index => $searchable) {
                $method = $index === 0 ? 'where' : 'orWhere';

                $query->{$method}(function ($query) use ($searchable, $value, $trashed) {
                    // Apply trashed filter scope
                    if ($trashed === '0') {
                        $query->withoutTrashed();
                    } elseif ($trashed === '2') {
                        $query->onlyTrashed();
                    }

                    // Add the LIKE condition
                    $query->where($searchable, 'LIKE', '%' . $value . '%');
                });
            }
        });
    }

    /**
     * @return void
     */
    private function queryFilters(): void
    {
        foreach ($this->filters() as $name => $value) {
            if(is_array($value)) {
                continue;
            }
            if (!method_exists($this, $name)) {
                continue;
            }
            if (strlen($value)) {
                $this->$name($value);
            } else {
                $this->$name('');
            }
        }
    }

    /**
     * @return void
     */
    private function queryRouteModel(): void
    {
        foreach ($this->routeModelParams() as $name) {
            if (!method_exists($this, $name)) {
                continue;
            }
            $this->$name('');
        }
    }

    /**
     * @return void
     */
    public function queryJoins(): void
    {
        $search = request('search');
        if (!empty( $search ) && count($this->joins) > 0) {
            foreach ($this->joins as $join) {
                if (!method_exists($this, $join)) {
                    continue;
                }
                if (strlen($search)) {
                    $this->$join($search);
                } else {
                    $this->$join('');
                }
            }
        }
    }

    /**
     * @return void
     */
    public function queryExcludes(): void
    {
        $params = request('exclude');
        if ( !empty($params) && count($this->excludes) > 0) {
            foreach ($this->excludes as $exclude) {
                if (!method_exists($this, $exclude)) {
                    continue;
                }
                if (strlen($params)) {
                    $this->$exclude($params);
                } else {
                    $this->$exclude('');
                }
            }
        }
    }

    /**
     * @return void
     */
    public function queryOnly(): void
    {
        $params = request('only');
        if (!empty($params) && count( $this->only) > 0) {
            foreach ($this->only as $only) {
                if (!method_exists($this, $only)) {
                    continue;
                }
                if (!empty($params)) {
                    $this->$only($params);
                } else {
                    $this->$only('');
                }
            }
        }
    }
}
