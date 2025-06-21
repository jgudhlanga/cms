<?php

namespace App\Repositories\Religions;


use App\DTO\Religions\ReligionDto;
use App\Http\Filters\Shared\SharedNameFilter;
use App\Models\Religions\Religion;
use App\Repositories\Base\BaseRepository;
use App\Repositories\Religions\interface\IReligionRepository;

class ReligionRepository extends BaseRepository implements IReligionRepository
{
    public function __construct(protected Religion $religion)
    {
        parent::__construct($this->religion);
    }

    public function create(ReligionDto $dto): Religion
    {
        return $this->religion->create($this->getFields($dto))->refresh();
    }

    public function update(Religion $religion, ReligionDto $dto): Religion
    {
        return tap($religion)->update($this->getFields($dto));
    }

    public function allFilter($columns = ['*'], SharedNameFilter $filters = null)
    {
        return $this->religion
            ->select($columns)
            ->filter($filters)
            ->orderBy('name')
            ->orderBy('deleted_at')
            ->paginate()
            ->withQueryString();
    }

    private function getFields(ReligionDto $dto): array
    {
        return [
            'name' => $dto->name,
            'description' => $dto->description,
        ];
    }
}
