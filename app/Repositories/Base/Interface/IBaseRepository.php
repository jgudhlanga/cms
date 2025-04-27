<?php

namespace App\Repositories\Base\Interface;

use Illuminate\Database\Eloquent\Model;

interface IBaseRepository
{
    public function find(Model $model);

    public function findTrashed(string $id);
    public function allTrashed();
    public function allCount(): int;

    public function findBy(array $data);

    public function findOneBy(array $data);

    public function delete(Model $model, bool $force = false): bool | null;

    public function restore(Model $model): bool;

    public function auditTrail(Model $model): mixed;
}
