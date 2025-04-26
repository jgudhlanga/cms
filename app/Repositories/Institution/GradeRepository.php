<?php

namespace App\Repositories\Institution;

use App\DTO\Institution\GradeDto;
use App\Http\Filters\Shared\SharedNameFilter;
use App\Models\Institution\Grade;
use App\Repositories\Base\BaseRepository;
use App\Repositories\Institution\interface\IGradeRepository;

class GradeRepository extends BaseRepository implements IGradeRepository
{
    public function __construct(protected Grade $grade)
    {
        parent::__construct($this->grade);
    }

    public function create(GradeDto $dto): Grade
    {
        return $this->grade->create([
            'name' => $dto->name,
            'description' => $dto->description,
        ])->refresh();
    }

    public function update(Grade $grade, GradeDto $dto): Grade
    {
        return tap($grade)->update([
            'name' => $dto->name,
            'description' => $dto->description,
        ]);
    }

    public function allFilter($columns = ['*'], SharedNameFilter $filters = null)
    {
        return $this->grade
            ->select($columns)
            ->filter($filters)
            ->orderBy('name')
            ->orderBy('deleted_at')
            ->paginate()
            ->withQueryString();
    }
}
