<?php

namespace App\Repositories\Shared;


use App\DTO\Shared\FeeTypeDto;
use App\Http\Filters\Shared\SharedNameFilter;
use App\Models\Shared\FeeType;
use App\Repositories\Base\BaseRepository;
use App\Repositories\Shared\interface\IFeeTypeRepository;
use Illuminate\Database\Eloquent\Model;

class FeeTypeRepository extends BaseRepository implements IFeeTypeRepository
{
    public function __construct(protected FeeType $feeType)
    {
        parent::__construct($this->feeType);
    }

    public function create(FeeTypeDto $dto): Model
    {
        return $this->feeType->create($this->getFields($dto))->refresh();
    }

    public function update(FeeType $feeType, FeeTypeDto $dto): FeeType
    {
        return tap($feeType)->update($this->getFields($dto));
    }

    public function allFilter($columns = ['*'], SharedNameFilter $filters = null)
    {
        return $this->feeType
            ->select($columns)
            ->filter($filters)
            ->orderBy('position')
            ->orderBy('name')
            ->orderBy('deleted_at')
            ->paginate()
            ->withQueryString();
    }

    /**
     * @param FeeTypeDto $dto
     * @return array
     */
    public function getFields(FeeTypeDto $dto): array
    {
        return [
            'name' => $dto->name,
            'description' => $dto->description,
        ];
    }
}
