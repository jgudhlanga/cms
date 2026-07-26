<?php

namespace App\Repositories\AcademicCalendars;

use App\DTO\AcademicCalendars\SemesterDto;
use App\Http\Filters\AcademicCalendars\SemesterFilter;
use App\Http\Filters\Shared\SharedNameFilter;
use App\Models\AcademicCalendars\Semester;
use App\Repositories\AcademicCalendars\interface\ISemesterRepository;
use App\Repositories\Base\BaseRepository;
use Illuminate\Database\Eloquent\Model;

class SemesterRepository extends BaseRepository implements ISemesterRepository
{
    public function __construct(protected Semester $semester)
    {
        parent::__construct($this->semester);
    }

    public function create(SemesterDto $dto): Model
    {
        return $this->semester->create($this->getFields($dto))->refresh();
    }

    public function update(Semester $semester, SemesterDto $dto): Semester
    {
        return tap($semester)->update($this->getFields($dto));
    }

    public function allFilter($columns = ['*'], SharedNameFilter|SemesterFilter|null $filters = null)
    {
        return $this->semester
            ->select($columns)
            ->filter($filters)
            ->orderBy('name')
            ->orderBy('deleted_at')
            ->paginate()
            ->withQueryString();
    }

    private function getFields(SemesterDto $dto): array
    {
        return [
            'name' => $dto->name,
            'description' => $dto->description,
        ];
    }
}
