<?php

namespace App\JsonApi\V1\HMS\HostelNotices\Filters;

use App\Models\Students\Student;
use App\Services\HMS\HostelNoticeAudienceService;
use Illuminate\Database\Eloquent\Builder;
use LaravelJsonApi\Eloquent\Contracts\Filter;
use LaravelJsonApi\Eloquent\Filters\Concerns\IsSingular;

class NoticeStudentFilter implements Filter
{
    use IsSingular;

    public function key(): string
    {
        return 'student';
    }

    public function apply($query, $value): Builder
    {
        $student = Student::query()->find($value);

        if ($student === null) {
            return $query->whereRaw('0 = 1');
        }

        return app(HostelNoticeAudienceService::class)->publishedForStudent($student);
    }
}
