<?php

namespace App\Repositories\Institution;

use App\DTO\Institution\AssessmentTypeDto;
use App\Http\Filters\Shared\SharedNameFilter;
use App\Models\Institution\AssessmentType;
use App\Repositories\Base\BaseRepository;
use App\Repositories\Institution\interface\IAssessmentTypeRepository;

class AssessmentTypeRepository extends BaseRepository implements IAssessmentTypeRepository
{
    public function __construct(protected AssessmentType $assessmentType)
    {
        parent::__construct($this->assessmentType);
    }

    public function create(AssessmentTypeDto $dto): AssessmentType
    {
        return $this->assessmentType->create($this->getFields($dto))->refresh();
    }

    public function update(AssessmentType $assessmentType, AssessmentTypeDto $dto): AssessmentType
    {
        return tap($assessmentType)->update($this->getFields($dto))->refresh();
    }

    public function allFilter($columns = ['*'], ?SharedNameFilter $filters = null)
    {
        return $this->assessmentType
            ->select($columns)
            ->filter($filters)
            ->orderBy('created_at')
            ->orderBy('modes_of_study')
            ->orderBy('deleted_at')
            ->paginate()
            ->withQueryString();
    }

    private function getFields(AssessmentTypeDto $dto): array
    {
        return [
            'name' => $dto->name,
            'modes_of_study' => $dto->modes_of_study,
            'description' => $dto->description,
        ];
    }
}
