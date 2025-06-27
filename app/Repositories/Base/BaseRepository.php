<?php

namespace App\Repositories\Base;

use App\Http\Requests\Shared\PositionRequest;
use App\Repositories\Base\Interface\IBaseRepository;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

class BaseRepository implements IBaseRepository
{

    public function __construct(protected Model $model)
    {
    }

    public function find(Model $model): Model
    {
        return $model->fresh();
    }

    public function findTrashed(string $id)
    {
        return $this->model->withTrashed()->find($id);
    }

    public function findBy(array $data): array|Collection
    {
        return $this->model->where($data)->get();
    }

    public function findOneBy(array $data): null|Model
    {
        return $this->model->where($data)->first();
    }

    public function delete(Model $model, bool $force = false): bool|null
    {
        if ($force) {
            return $model->forceDelete();
        }
        return $model->delete();
    }

    public function restore(Model $model): bool
    {
        return $model->restore();
    }

    public function auditTrail(Model $model): mixed
    {
        return $model->activities()->orderBy('created_at', 'desc')->get();
    }

    public function allTrashed()
    {
        return $this->model->onlyTrashed();
    }

    public function allCount(): int
    {
        return $this->model->all()->count();
    }

    public function getAllWithTrashed()
    {
        return $this->model->withTrashed();
    }

    public function movePosition(Model $model, PositionRequest $request): void
    {
        $newPosition = $request->input('position');
        $originalPosition = $model->position;
        if ($newPosition !== $originalPosition) {
            if ($newPosition < $originalPosition) {
                // Move other courses down
                $this->model->where('position', '>=', $newPosition)
                    ->where('position', '<', $originalPosition)
                    ->increment('position');
            } else {
                // Move other courses up
                $this->model->where('position', '<=', $newPosition)
                    ->where('position', '>', $originalPosition)
                    ->decrement('position');
            }
        }
        $model->update(['position' => $newPosition]);
    }
}
