<?php

namespace App\Repositories\AcademicCalendars;

use App\DTO\AcademicCalendars\AcademicYearOptionDto;
use App\Http\Filters\AcademicCalendars\AcademicYearOptionFilter;
use App\Http\Filters\Shared\SharedNameFilter;
use App\Models\AcademicCalendars\AcademicYearOption;
use App\Repositories\AcademicCalendars\interface\IAcademicYearOptionRepository;
use App\Repositories\Base\BaseRepository;
use Illuminate\Database\Eloquent\Model;

class AcademicYearOptionRepository extends BaseRepository implements IAcademicYearOptionRepository
{
    public function __construct(protected AcademicYearOption $academicYearOption)
    {
        parent::__construct($this->academicYearOption);
    }

    public function create(AcademicYearOptionDto $dto): Model
    {
        return $this->academicYearOption->create($this->getFields($dto))->refresh();
    }

    public function update(AcademicYearOption $academicYearOption, AcademicYearOptionDto $dto): AcademicYearOption
    {
        return tap($academicYearOption)->update($this->getFields($dto));
    }

    public function allFilter($columns = ['*'], SharedNameFilter|AcademicYearOptionFilter|null $filters = null)
    {
        return $this->academicYearOption
            ->select($columns)
            ->filter($filters)
            ->orderBy('name')
            ->orderBy('deleted_at')
            ->paginate()
            ->withQueryString();
    }

    private function getFields(AcademicYearOptionDto $dto): array
    {
        return [
            'name' => $dto->name,
            'description' => $dto->description,
        ];
    }
}
