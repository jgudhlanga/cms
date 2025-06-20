<?php

namespace App\Repositories\Shared;


use App\DTO\Shared\NextOfKinDto;
use App\Models\Shared\NextOfKin;
use App\Repositories\Base\BaseRepository;
use App\Repositories\Shared\interface\INextOfKinRepository;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class NextOfKinRepository extends BaseRepository implements INextOfKinRepository
{
    public function __construct(protected NextOfKin $nextOfKin)
    {
        parent::__construct($this->nextOfKin);
    }

    public function create(Model $model, NextOfKinDto $dto): NextOfKin
    {
        return NextOfKin::create(
            array_merge([
                'tenant_id' => $model->tenant_id ?? @Auth::user()->tenant_id,
                'kinnable_id' => $model->id,
                'kinnable_type' => get_class($model),
            ],
                $this->getFields($dto))
        );
    }

    public function update(NextOfKin $nextOfKin, NextOfKinDto $dto): NextOfKin
    {
        return tap($nextOfKin)->update($this->getFields($dto));
    }

    private function getFields(NextOfKinDto $dto): array
    {
        return [
            'name' => $dto->name,
            'relationship_id' => $dto->relationship_id,
        ];
    }
}
