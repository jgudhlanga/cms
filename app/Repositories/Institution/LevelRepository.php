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
        return $this->level->create([
            'name' => $dto->name,
            'description' => $dto->description,
        ])->refresh();
    }

    public function update(Level $level, LevelDto $dto): Level
    {
        return tap($level)->update([
            'name' => $dto->name,
            'description' => $dto->description,
        ]);
    }

    public function allFilter($columns = ['*'], SharedNameFilter $filters = null)
    {
        return $this->level
            ->select($columns)
            ->filter($filters)
            ->orderBy('name')
            ->orderBy('deleted_at')
            ->paginate()
            ->withQueryString();
    }
}
