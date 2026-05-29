<?php

namespace App\JsonApi\V1\Students\StudentPrograms\Filters;

use Illuminate\Database\Eloquent\Builder;
use LaravelJsonApi\Eloquent\Contracts\Filter;
use LaravelJsonApi\Eloquent\Filters\Concerns\IsSingular;

class StudentFilter implements Filter
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
