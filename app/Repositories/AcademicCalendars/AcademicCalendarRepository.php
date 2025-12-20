<?php

namespace App\Repositories\AcademicCalendars;

use App\DTO\AcademicCalendars\AcademicCalendarDto;
use App\Http\Filters\AcademicCalendars\AcademicCalendarFilter;
use App\Models\AcademicCalendars\AcademicCalendar;
use App\Repositories\AcademicCalendars\Interface\IAcademicCalendarRepository;
use App\Repositories\Base\BaseRepository;

class AcademicCalendarRepository extends BaseRepository implements IAcademicCalendarRepository
{
    public function __construct(protected AcademicCalendar $academicCalendar)
    {
        parent::__construct($this->academicCalendar);
    }

    public function create(AcademicCalendarDto $dto): AcademicCalendar
    {
        $academicCalendar = $this->academicCalendar->create($this->getFields($dto));
        return $academicCalendar->fresh();
    }

    public function update(AcademicCalendar $academicCalendar, AcademicCalendarDto $dto): AcademicCalendar
    {
        $academicCalendar = tap($academicCalendar)->update($this->getFields($dto));

        return $academicCalendar->fresh();
    }

    public function allFilter($columns = ['*'], ?AcademicCalendarFilter $filters = null)
    {
        return $this->academicCalendar
            ->select($columns)
            ->filter($filters)
            ->orderBy('academic_calendars.calendar_year', 'desc')
            ->orderBy('deleted_at')
            ->paginate()
            ->withQueryString();
    }

    /**
     * @param AcademicCalendarDto $dto
     * @return array
     */
    public function getFields(AcademicCalendarDto $dto): array
    {
        return [
            'name' => $dto->name,
            'calendar_year' => $dto->calendar_year,
            'calendar_type' => $dto->calendar_type,
            'opening_date' => $dto->opening_date,
            'closing_date' => $dto->closing_date,
            'description' => $dto->description,
        ];
    }

}
