<?php

namespace App\Repositories\Institution;

use App\DTO\Institution\SubjectDto;
use App\Http\Filters\Shared\SharedNameFilter;
use App\Models\Institution\Subject;
use App\Repositories\Base\BaseRepository;
use App\Repositories\Institution\interface\ISubjectRepository;

class SubjectRepository extends BaseRepository implements ISubjectRepository
{
    public function __construct(protected Subject $subject)
    {
        parent::__construct($this->subject);
    }

    public function create(SubjectDto $dto): Subject
    {
        return $this->subject->create([
            'name' => $dto->name,
            'description' => $dto->description,
        ])->refresh();
    }

    public function update(Subject $subject, SubjectDto $dto): Subject
    {
        return tap($subject)->update([
            'name' => $dto->name,
            'description' => $dto->description,
        ]);
    }

    public function allFilter($columns = ['*'], SharedNameFilter $filters = null)
    {
        return $this->subject
            ->select($columns)
            ->filter($filters)
            ->orderBy('name')
            ->orderBy('deleted_at')
            ->paginate()
            ->withQueryString();
    }
}
