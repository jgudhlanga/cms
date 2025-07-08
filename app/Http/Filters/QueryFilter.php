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
        $this->builder
            ->when($value, function ($query, $value) use ($trashed) {
                foreach ($this->searchable as $index => $searchable) {
                    if ($index == 0) {
                        if ($trashed == '2') {
                            $query->where(function ($query) use ($searchable, $value) {
                                $query->onlyTrashed()
                                    ->where($searchable, 'LIKE', '%' . $value . '%');
                            });
                        } else {
                            $query->where($searchable, 'LIKE', '%' . $value . '%');
                        }
                    } else {
                        if ($trashed == '0') {
                            $query->orWhere(function ($query) use ($searchable, $value) {
                                $query->withoutTrashed()
                                    ->where($searchable, 'LIKE', '%' . $value . '%');
                            });
                        } else if ($trashed == '2') {
                            $query->orWhere(function ($query) use ($searchable, $value) {
                                $query->onlyTrashed()
                                    ->where($searchable, 'LIKE', '%' . $value . '%');
                            });
                        } else {
                            $query->orWhere($searchable, 'LIKE', '%' . $value . '%');
                        }
                    }
                }
            });
    }

    /**
     * @return void
     */
    private function queryFilters(): void
    {
        foreach ($this->filters() as $name => $value) {
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
        if (count($this->joins) > 0) {
            $search = request('search');
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
        if (count($this->excludes) > 0) {
            $params = request('exclude');
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
        if (count($this->only) > 0) {
            $params = request('only');
            foreach ($this->only as $only) {
                if (!method_exists($this, $only)) {
                    continue;
                }
                if (strlen($params)) {
                    $this->$only($params);
                } else {
                    $this->$only('');
                }
            }
        }
    }
}
