<?php

namespace App\Repositories\Districts;


use App\DTO\Districts\DistrictDto;
use App\Http\Filters\Shared\SharedNameFilter;
use App\Models\Districts\District;
use App\Repositories\Base\BaseRepository;
use App\Repositories\Districts\interface\IDistrictRepository;

class DistrictRepository extends BaseRepository implements IDistrictRepository
{
    public function __construct(protected District $district)
    {
        parent::__construct($this->district);
    }

    public function create(DistrictDto $dto): District
    {
        return $this->district->create([
            'name' => $dto->name,
            'description' => $dto->description,
        ])->refresh();
    }

    public function update(District $district, DistrictDto $dto): District
    {
        return tap($district)->update([
            'name' => $dto->name,
            'description' => $dto->description,
        ]);
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
}
