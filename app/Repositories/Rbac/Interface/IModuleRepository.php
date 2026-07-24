<?php

namespace App\Repositories\Rbac\Interface;

use App\DTO\Rbac\ModuleDto;
use App\Http\Filters\Rbac\ModuleFilter;
use App\Models\Rbac\Module;
use App\Repositories\Base\Interface\IBaseRepository;

interface IModuleRepository extends IBaseRepository
{
    public function create(ModuleDto $dto);

    public function update(Module $module, ModuleDto $dto);

    public function updateSettings(Module $module, bool $status, ?array $settings = null): Module;

    public function allFilter($columns = ['*'], ?ModuleFilter $filters = null);
}
