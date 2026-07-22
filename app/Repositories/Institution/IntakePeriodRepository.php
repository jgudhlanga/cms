<?php

namespace App\Repositories\Institution;

use App\DTO\Institution\IntakePeriodDto;
use App\Http\Filters\Shared\SharedNameFilter;
use App\Models\Institution\IntakePeriod;
use App\Repositories\Base\BaseRepository;
use App\Repositories\Institution\interface\IIntakePeriodRepository;
use Carbon\Carbon;

class IntakePeriodRepository extends BaseRepository implements IIntakePeriodRepository
{
    public function __construct(protected IntakePeriod $intakePeriod)
    {
        parent::__construct($this->intakePeriod);
    }

    public function create(IntakePeriodDto $dto): IntakePeriod
    {
        return $this->intakePeriod->create($this->getFields($dto))->refresh();
    }

    public function update(IntakePeriod $intakePeriod, IntakePeriodDto $dto): IntakePeriod
    {
        return tap($intakePeriod)->update($this->getFields($dto))->refresh();
    }

    public function allFilter($columns = ['*'], ?SharedNameFilter $filters = null)
    {
        return $this->intakePeriod
            ->select($columns)
            ->filter($filters)
            ->where('is_active', 1)
            ->orderBy('end_date', 'desc')
            ->orderBy('name')
            ->orderBy('description')
            ->orderBy('deleted_at')
            ->paginate()
            ->withQueryString();
    }

    private function getFields(IntakePeriodDto $dto): array
    {
        return [
            'name' => $dto->name,
            'start_date' => Carbon::parse($dto->start_date)->format('Y-m-d'),
            'end_date' => Carbon::parse($dto->end_date)->format('Y-m-d'),
            'description' => $dto->description,
            'status' => $dto->status,
            'is_continuous' => $dto->is_continuous,
        ];
    }
}
