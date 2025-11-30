<?php

namespace App\Repositories\Acl;

use App\DTO\Acl\ModuleDto;
use App\Http\Filters\Acl\ModuleFilter;
use App\Models\Acl\Module;
use App\Repositories\Base\BaseRepository;
use App\Repositories\Acl\Interface\IModuleRepository;

class ModuleRepository extends BaseRepository implements IModuleRepository
{
    public function __construct(protected Module $module)
    {
        parent::__construct($this->module);
    }

    public function create(ModuleDto $dto): Module
    {
        return $this->module->create([
            'title' => $dto->title,
            'description' => $dto->description,
        ])->refresh();
    }

    public function update(Module $module, ModuleDto $dto): Module
    {
        return tap($module)->update([
            'title' => $dto->title,
            'description' => $dto->description,
        ]);
    }

    public function allFilter($columns = ['*'], ?ModuleFilter $filters = null)
    {
        return $this->module
			->select($columns)
			->filter($filters)
			->orderBy('title')
			->orderBy('deleted_at')
			->paginate()
			->withQueryString();
    }
}
