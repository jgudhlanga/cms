<?php

namespace App\Repositories\Institution;

use App\DTO\Institution\FeeStructureDto;
use App\Http\Filters\Institution\FeeStructureFilter;
use App\Models\Institution\FeeStructure;
use App\Repositories\Base\BaseRepository;
use App\Repositories\Institution\interface\IFeeStructureRepository;

class FeeStructureRepository extends BaseRepository implements IFeeStructureRepository
{
    public function __construct(protected FeeStructure $feeStructure)
    {
        parent::__construct($this->feeStructure);
    }

    public function create(FeeStructureDto $dto): FeeStructure
    {
        return $this->feeStructure->create($this->getFields($dto))->refresh();
    }

    public function update(FeeStructure $feeStructure, FeeStructureDto $dto): FeeStructure
    {
        return tap($feeStructure)->update($this->getFields($dto))->refresh();
    }

    public function allFilter($columns = ['*'], FeeStructureFilter $filters = null)
    {
        return $this->feeStructure
            ->select($columns)
            ->filter($filters)
            ->orderBy('created_at')
            ->orderBy('deleted_at')
            ->paginate()
            ->withQueryString();
    }

    private function getFields(FeeStructureDto $dto): array
    {
        return [
            'fee_type_id' => $dto->fee_type_id,
            'level_id' => $dto->level_id,
            'mode_of_study_id' => $dto->mode_of_study_id,
            'amount' => $dto->amount,
            'local_fca_amount' => $dto->local_fca_amount
        ];
    }

}
