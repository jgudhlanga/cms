<?php

namespace App\Repositories\AcademicCalendars;

use App\DTO\AcademicYears\AcademicCalendarDto;
use App\Http\Filters\AcademicCalendars\AcademicCalendarFilter;
use App\Models\AcademicCalendars\AcademicCalendar;
use App\Repositories\AcademicCalendars\Interface\IAcademicCalendarRepository;
use App\Repositories\Base\BaseRepository;
use Carbon\Carbon;

class AcademicCalendarRepository extends BaseRepository implements IAcademicCalendarRepository
{
    public function __construct(protected AcademicCalendar $academicCalendar)
    {
        parent::__construct($this->academicCalendar);
    }

    public function create(AcademicCalendarDto $dto): AcademicCalendar
    {
        return $this->academicCalendar->create($this->extractFields($dto))->refresh();
    }

    public function update(AcademicCalendar $academicCalendar, AcademicCalendarDto $dto): AcademicCalendar
    {
        return tap($academicCalendar)->update($this->extractFields($dto));
    }

    public function allFilter($columns = ['*'], ?AcademicCalendarFilter $filters = null)
    {
        return $this->academicCalendar
            ->select($columns)
            ->filter($filters)
            ->orderBy('name')
            ->orderBy('deleted_at')
            ->paginate()
            ->withQueryString();
    }

    /**
     * @param AcademicCalendarDto $dto
     * @return array
     */
    private function extractFields(AcademicCalendarDto $dto): array
    {
        return [
            'name' => $dto->name,
            'type' => $dto->type,
            'opening_date' => Carbon::parse($dto->opening_date)->format('Y-m-d'),
            'closing_date' => Carbon::parse($dto->closing_date)->format('Y-m-d'),
            'description' => $dto->description,
        ];
    }
}
