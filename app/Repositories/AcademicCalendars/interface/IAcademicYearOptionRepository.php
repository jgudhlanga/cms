<?php

namespace App\Repositories\AcademicCalendars\interface;

use App\DTO\AcademicCalendars\AcademicYearOptionDto;
use App\Http\Filters\Shared\SharedNameFilter;
use App\Models\AcademicCalendars\AcademicYearOption;
use App\Repositories\Base\Interface\IBaseRepository;
use Illuminate\Database\Eloquent\Model;

interface IAcademicYearOptionRepository extends IBaseRepository
{
    public function create(AcademicYearOptionDto $dto): Model;

    public function update(AcademicYearOption $academicYearOption, AcademicYearOptionDto $dto): AcademicYearOption;

    public function allFilter($columns = ['*'], ?SharedNameFilter $filters = null);
}
