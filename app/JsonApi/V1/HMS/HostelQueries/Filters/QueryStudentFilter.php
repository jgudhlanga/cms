<?php

namespace App\JsonApi\V1\HMS\HostelQueries\Filters;

use Illuminate\Database\Eloquent\Builder;
use LaravelJsonApi\Eloquent\Contracts\Filter;
use LaravelJsonApi\Eloquent\Filters\Concerns\IsSingular;

class QueryStudentFilter implements Filter
{
    use IsSingular;

    public function key(): string
    {
        return 'student';
    }

    public function apply($query, $value): Builder
    {
        return $query->where('student_id', $value);
    }
}
