<?php

namespace App\Repositories\Acl\Interface;

use App\DTO\Acl\ModuleDto;
use App\Http\Filters\Acl\ModuleFilter;
use App\Models\Acl\Module;
use App\Repositories\Base\Interface\IBaseRepository;

interface IModuleRepository extends IBaseRepository
{
    public function create(ModuleDto $dto);

    public function update(Module $module, ModuleDto $dto);

    public function updateSettings(Module $module, bool $status, ?array $settings = null): Module;

    public function allFilter($columns = ['*'], ?ModuleFilter $filters = null);
}
