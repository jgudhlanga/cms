<?php

namespace App\Repositories\Institution\interface;

use App\DTO\Institution\AssessmentCalendarDto;
use App\Http\Filters\Institution\AssessmentCalendarFilter;
use App\Models\Institution\AssessmentCalendar\AssessmentCalendar;
use App\Repositories\Base\Interface\IBaseRepository;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface IAssessmentCalendarRepository extends IBaseRepository
{
    public function create(AssessmentCalendarDto $dto): AssessmentCalendar;

    public function update(AssessmentCalendar $assessmentCalendar, AssessmentCalendarDto $dto): AssessmentCalendar;

    public function allFilter(int $assessmentTypeId, array $columns = ['*'], ?AssessmentCalendarFilter $filters = null): LengthAwarePaginator;

    public function allTrashedForAssessmentType(int $assessmentTypeId);
}
