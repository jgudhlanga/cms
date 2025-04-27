<?php

namespace App\Repositories\Institution;

use App\DTO\Institution\DivisionDto;
use App\Http\Filters\Shared\SharedNameFilter;
use App\Models\Institution\Division;
use App\Repositories\Base\BaseRepository;
use App\Repositories\Institution\interface\IDivisionRepository;

class DivisionRepository extends BaseRepository implements IDivisionRepository
{
    public function __construct(protected Division $division)
    {
        parent::__construct($this->division);
    }

    public function create(DivisionDto $dto): Division
    {
        return $this->division->create([
            'name' => $dto->name,
            'description' => $dto->description,
        ])->refresh();
    }

    public function update(Division $division, DivisionDto $dto): Division
    {
        return tap($division)->update([
            'name' => $dto->name,
            'description' => $dto->description,
        ]);
    }

    public function allFilter($columns = ['*'], SharedNameFilter $filters = null)
    {
        return $this->division
            ->select($columns)
            ->filter($filters)
            ->orderBy('name')
            ->orderBy('deleted_at')
            ->paginate()
            ->withQueryString();
    }
}
