<?php

namespace App\Repositories\Institution;

use App\DTO\Institution\IntakePeriodDto;
use App\Enums\Institution\IntakePeriodStatusEnum;
use App\Http\Filters\Shared\SharedNameFilter;
use App\Models\Institution\IntakePeriod;
use App\Repositories\Base\BaseRepository;
use App\Repositories\Institution\interface\IIntakePeriodRepository;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class IntakePeriodRepository extends BaseRepository implements IIntakePeriodRepository
{
    public function __construct(protected IntakePeriod $intakePeriod)
    {
        parent::__construct($this->intakePeriod);
    }

    public function create(IntakePeriodDto $dto): IntakePeriod
    {
        return DB::transaction(function () use ($dto): IntakePeriod {
            $intakePeriod = $this->intakePeriod->create($this->getFields($dto))->refresh();
            $this->suspendOpenContinuousWhenRegularOpened($dto);

            return $intakePeriod;
        });
    }

    public function update(IntakePeriod $intakePeriod, IntakePeriodDto $dto): IntakePeriod
    {
        return DB::transaction(function () use ($intakePeriod, $dto): IntakePeriod {
            $wasOpenRegular = ! $intakePeriod->is_continuous
                && $intakePeriod->status === IntakePeriodStatusEnum::Open;

            $intakePeriod = tap($intakePeriod)->update($this->getFields($dto))->refresh();
            $this->suspendOpenContinuousWhenRegularOpened($dto);
            $this->openContinuousWhenLastOpenRegularClosed($wasOpenRegular, $dto);

            return $intakePeriod;
        });
    }

    public function allFilter($columns = ['*'], ?SharedNameFilter $filters = null)
    {
        $mostRecentRegularId = (int) (IntakePeriod::query()
            ->regular()
            ->where('is_active', 1)
            ->orderByDesc('end_date')
            ->value('id') ?? 0);

        return $this->intakePeriod
            ->select($columns)
            ->filter($filters)
            ->where('is_active', 1)
            ->orderByRaw(
                'CASE WHEN id = ? THEN 0 WHEN is_continuous = 1 THEN 1 ELSE 2 END',
                [$mostRecentRegularId]
            )
            ->orderByDesc('end_date')
            ->orderBy('name')
            ->orderBy('description')
            ->orderBy('deleted_at')
            ->paginate()
            ->withQueryString();
    }

    private function suspendOpenContinuousWhenRegularOpened(IntakePeriodDto $dto): void
    {
        if ($dto->is_continuous || $dto->status !== IntakePeriodStatusEnum::Open->value) {
            return;
        }

        IntakePeriod::query()
            ->continuous()
            ->where('is_active', true)
            ->where('status', IntakePeriodStatusEnum::Open)
            ->update(['status' => IntakePeriodStatusEnum::Suspended]);
    }

    private function openContinuousWhenLastOpenRegularClosed(bool $wasOpenRegular, IntakePeriodDto $dto): void
    {
        if (! $wasOpenRegular || $dto->is_continuous || $dto->status === IntakePeriodStatusEnum::Open->value) {
            return;
        }

        $hasOpenRegular = IntakePeriod::query()
            ->regular()
            ->where('is_active', true)
            ->where('status', IntakePeriodStatusEnum::Open)
            ->exists();

        if ($hasOpenRegular) {
            return;
        }

        $continuous = IntakePeriod::query()
            ->continuous()
            ->where('is_active', true)
            ->where('status', IntakePeriodStatusEnum::Suspended)
            ->orderByDesc('end_date')
            ->first();

        $continuous?->update(['status' => IntakePeriodStatusEnum::Open]);
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
