<?php

namespace App\Repositories\AcademicCalendars\interface;

use App\DTO\AcademicCalendars\SemesterDto;
use App\Http\Filters\AcademicCalendars\SemesterFilter;
use App\Http\Filters\Shared\SharedNameFilter;
use App\Models\AcademicCalendars\Semester;
use App\Repositories\Base\Interface\IBaseRepository;
use Illuminate\Database\Eloquent\Model;

interface ISemesterRepository extends IBaseRepository
{
    public function create(SemesterDto $dto): Model;

    public function update(Semester $semester, SemesterDto $dto): Semester;

    public function allFilter($columns = ['*'], SharedNameFilter|SemesterFilter|null $filters = null);
}
