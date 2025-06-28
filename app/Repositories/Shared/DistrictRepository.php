<?php

namespace App\Repositories\Shared;


use App\DTO\Shared\DistrictDto;
use App\Http\Filters\Shared\SharedNameFilter;
use App\Models\Shared\District;
use App\Repositories\Base\BaseRepository;
use App\Repositories\Shared\interface\IDistrictRepository;

class DistrictRepository extends BaseRepository implements IDistrictRepository
{
    public function __construct(protected District $district)
    {
        parent::__construct($this->district);
    }

    public function create(DistrictDto $dto): District
    {
        return $this->district->create($this->getFields($dto))->refresh();
    }

    public function update(District $district, DistrictDto $dto): District
    {
        return tap($district)->update($this->getFields($dto));
    }

    public function allFilter($columns = ['*'], SharedNameFilter $filters = null)
    {
        return $this->district
            ->select($columns)
            ->filter($filters)
            ->orderBy('province_id')
            ->orderBy('deleted_at')
            ->paginate()
            ->withQueryString();
    }

    private function getFields(DistrictDto $dto): array
    {
        return [
            'name' => $dto->name,
            'province_id' => $dto->province_id,
            'description' => $dto->description,
        ];
    }
}
