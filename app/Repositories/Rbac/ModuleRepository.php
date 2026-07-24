<?php

namespace App\Repositories\Rbac;

use App\DTO\Rbac\ModuleDto;
use App\Http\Filters\Rbac\ModuleFilter;
use App\Models\Rbac\Module;
use App\Repositories\Rbac\Interface\IModuleRepository;
use App\Repositories\Base\BaseRepository;

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

    public function updateSettings(Module $module, bool $status, ?array $settings = null): Module
    {
        $payload = ['status' => $status];

        if ($settings !== null) {
            $payload['settings'] = $settings;
        }

        return tap($module)->update($payload);
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
