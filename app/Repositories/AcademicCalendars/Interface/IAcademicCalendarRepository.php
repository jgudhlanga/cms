<?php

namespace App\Repositories\AcademicCalendars\Interface;

use App\DTO\AcademicYears\AcademicCalendarDto;
use App\Http\Filters\AcademicCalendars\AcademicCalendarFilter;
use App\Models\AcademicCalendars\AcademicCalendar;
use App\Repositories\Base\Interface\IBaseRepository;

interface IAcademicCalendarRepository extends IBaseRepository
{
    public function create(AcademicCalendarDto $dto);

    public function update(AcademicCalendar $academicCalendar, AcademicCalendarDto $dto);

    public function allFilter($columns = ['*'], AcademicCalendarFilter $filters = null);
}
