<?php

namespace App\Repositories\Institution;

use App\DTO\Institution\AssessmentCalendarDto;
use App\Http\Filters\Institution\AssessmentCalendarFilter;
use App\Models\Institution\AssessmentCalendar\AssessmentCalendar;
use App\Repositories\Base\BaseRepository;
use App\Repositories\Institution\interface\IAssessmentCalendarRepository;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class AssessmentCalendarRepository extends BaseRepository implements IAssessmentCalendarRepository
{
    public function __construct(protected AssessmentCalendar $assessmentCalendar)
    {
        parent::__construct($this->assessmentCalendar);
    }

    public function create(AssessmentCalendarDto $dto): AssessmentCalendar
    {
        return $this->assessmentCalendar->create($this->getFields($dto))->refresh();
    }

    public function update(AssessmentCalendar $assessmentCalendar, AssessmentCalendarDto $dto): AssessmentCalendar
    {
        return tap($assessmentCalendar)->update($this->getFields($dto))->refresh();
    }

    public function allFilter(int $assessmentTypeId, array $columns = ['*'], ?AssessmentCalendarFilter $filters = null): LengthAwarePaginator
    {
        return $this->assessmentCalendar
            ->with(['academicCalendar'])
            ->select($columns)
            ->where('assessment_type_id', $assessmentTypeId)
            ->filter($filters)
            ->orderByDesc('start_date')
            ->orderByDesc('created_at')
            ->paginate()
            ->withQueryString();
    }

    public function allTrashedForAssessmentType(int $assessmentTypeId)
    {
        return $this->assessmentCalendar
            ->onlyTrashed()
            ->where('assessment_type_id', $assessmentTypeId);
    }

    /**
     * @return array<string, mixed>
     */
    private function getFields(AssessmentCalendarDto $dto): array
    {
        return [
            'assessment_type_id' => $dto->assessment_type_id,
            'academic_calendar_id' => $dto->academic_calendar_id,
            'start_date' => $dto->start_date,
            'end_date' => $dto->end_date,
            'type' => $dto->type,
        ];
    }
}
