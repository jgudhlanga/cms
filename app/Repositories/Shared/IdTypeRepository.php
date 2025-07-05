<?php

namespace App\Repositories\Shared;


use App\DTO\Shared\IdTypeDto;
use App\Http\Filters\Shared\SharedNameFilter;
use App\Models\Shared\IdType;
use App\Repositories\Base\BaseRepository;
use App\Repositories\Shared\interface\IIdTypeRepository;

class IdTypeRepository extends BaseRepository implements IIdTypeRepository
{
    public function __construct(protected IdType $idType)
    {
        parent::__construct($this->idType);
    }

    public function create(IdTypeDto $dto): IdType
    {
        return $this->idType->create($this->getFields($dto))->refresh();
    }

    public function update(IdType $idType, IdTypeDto $dto): IdType
    {
        return tap($idType)->update($this->getFields($dto));
    }

    public function allFilter($columns = ['*'], SharedNameFilter $filters = null)
    {
        return $this->idType
            ->select($columns)
            ->filter($filters)
            ->orderBy('name')
            ->orderBy('deleted_at')
            ->paginate()
            ->withQueryString();
    }

    /**
     * @param IdTypeDto $dto
     * @return array
     */
    public function getFields(IdTypeDto $dto): array
    {
        return [
            'name' => $dto->name,
            'description' => $dto->description,
        ];
    }
}
