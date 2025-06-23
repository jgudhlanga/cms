<?php

namespace App\Repositories\Shared;


use App\DTO\Shared\SponsorTypeDto;
use App\Http\Filters\Shared\SharedNameFilter;
use App\Models\Shared\SponsorType;
use App\Repositories\Base\BaseRepository;
use App\Repositories\Shared\interface\ISponsorTypeRepository;

class SponsorTypeRepository extends BaseRepository implements ISponsorTypeRepository
{
    public function __construct(protected SponsorType $sponsorType)
    {
        parent::__construct($this->sponsorType);
    }

    public function create(SponsorTypeDto $dto): SponsorType
    {
        return $this->sponsorType->create($this->getFields($dto))->refresh();
    }

    public function update(SponsorType $sponsorType, SponsorTypeDto $dto): SponsorType
    {
        return tap($sponsorType)->update($this->getFields($dto));
    }

    private function getFields(SponsorTypeDto $dto): array
    {
        return [
            'name' => $dto->name,
            'description' => $dto->description,
        ];
    }

    public function allFilter($columns = ['*'], SharedNameFilter $filters = null)
    {
        return $this->sponsorType
            ->select($columns)
            ->filter($filters)
            ->orderBy('name')
            ->orderBy('deleted_at')
            ->paginate()
            ->withQueryString();
    }
}
