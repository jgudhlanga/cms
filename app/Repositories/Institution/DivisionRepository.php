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
        return $this->division->create($this->getFields($dto))->refresh();
    }

    public function update(Division $division, DivisionDto $dto): Division
    {
        return tap($division)->update($this->getFields($dto));
    }

    public function allFilter($columns = ['*'], SharedNameFilter $filters = null)
    {
        return $this->division
            ->select($columns)
            ->filter($filters)
            ->orderBy('position')
            ->orderBy('name')
            ->orderBy('deleted_at')
            ->paginate()
            ->withQueryString();
    }

    private function getFields(DivisionDto $dto): array
    {
        return [
            'name' => $dto->name,
            'description' => $dto->description,
        ];
    }
}
