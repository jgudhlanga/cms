<?php

namespace App\Repositories\Institution;


use App\DTO\Enrolments\ClassListDto;
use App\Models\Enrolments\ClassList;
use App\Repositories\Base\BaseRepository;
use App\Repositories\Institution\interface\IClassListRepository;

class ClassListRepository extends BaseRepository implements IClassListRepository
{
    public function __construct(protected ClassList $classList)
    {
        parent::__construct($this->classList);
    }

    public function create(ClassListDto $dto): ClassList
    {
        return $this->classList->create($this->getFields($dto))->refresh();
    }

    public function update(ClassList $classList, ClassListDto $dto): ClassList
    {
        return tap($classList)->update($this->getFields($dto))->refresh();
    }

    private function getFields(ClassListDto $dto): array
    {
        return [
            'student_program_id' => $dto->student_program_id,
            'type' => $dto->type,
            'attributes' => $dto->attributes,
        ];
    }
}
