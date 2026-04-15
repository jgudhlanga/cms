<?php

namespace App\Repositories\Institution;

use App\DTO\Institution\LevelDto;
use App\Http\Filters\Shared\SharedNameFilter;
use App\Models\Institution\Level;
use App\Repositories\Base\BaseRepository;
use App\Repositories\Institution\interface\ILevelRepository;

class LevelRepository extends BaseRepository implements ILevelRepository
{
    public function __construct(protected Level $level)
    {
        parent::__construct($this->level);
    }

    public function create(LevelDto $dto): Level
    {
        return $this->level->create($this->getFields($dto))->refresh();
    }

    public function update(Level $level, LevelDto $dto): Level
    {
        $level->update($this->getFields($dto));

        return $level->refresh();
    }

    private function getFields(LevelDto $dto): array
    {
        return [
            'name' => $dto->name,
            'description' => $dto->description,
            'allowed_applications_per_level' => $dto->allowed_applications_per_level,
            'show_on_current_application_period' => $dto->show_on_current_application_period,
            'has_application_fee_payment' => $dto->has_application_fee_payment,
            'calendar_type' => $dto->calendar_type,
        ];
    }

    public function allFilter($columns = ['*'], ?SharedNameFilter $filters = null)
    {
        return $this->level
            ->select($columns)
            ->filter($filters)
            ->orderBy('position')
            ->orderBy('name')
            ->orderBy('deleted_at')
            ->paginate()
            ->withQueryString();
    }
}
