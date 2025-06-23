<?php

namespace App\Repositories\AcademicLevels;

use App\DTO\AcademicLevels\AcademicLevelDto;
use App\Http\Filters\Shared\SharedNameFilter;
use App\Models\AcademicLevels\AcademicLevel;
use App\Repositories\AcademicLevels\interface\IAcademicLevelRepository;
use App\Repositories\Base\BaseRepository;

class AcademicLevelRepository extends BaseRepository implements IAcademicLevelRepository
{
    public function __construct(protected AcademicLevel $academicLevel)
    {
        parent::__construct($this->academicLevel);
    }

    public function create(AcademicLevelDto $dto): AcademicLevel
    {
        return $this->academicLevel->create($this->getFields($dto))->refresh();
    }

    public function update(AcademicLevel $academicLevel, AcademicLevelDto $dto): AcademicLevel
    {
        return tap($academicLevel)->update($this->getFields($dto));
    }

    private function getFields(AcademicLevelDto $dto): array
    {
        return [
            'name' => $dto->name,
            'description' => $dto->description,
        ];
    }

    public function allFilter($columns = ['*'], SharedNameFilter $filters = null)
    {
        return $this->academicLevel
            ->select($columns)
            ->filter($filters)
            ->orderBy('position')
            ->orderBy('name')
            ->orderBy('deleted_at')
            ->paginate()
            ->withQueryString();
    }
}
