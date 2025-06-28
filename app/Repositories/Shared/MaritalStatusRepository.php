<?php

namespace App\Repositories\Shared;


use App\DTO\Shared\MaritalStatusDto;
use App\Http\Filters\Shared\SharedTitleFilter;
use App\Models\Shared\MaritalStatus;
use App\Repositories\Base\BaseRepository;
use App\Repositories\Shared\interface\IMaritalStatusRepository;

class MaritalStatusRepository extends BaseRepository implements IMaritalStatusRepository
{
    public function __construct(protected MaritalStatus $maritalStatus)
    {
        parent::__construct($this->maritalStatus);
    }

    public function create(MaritalStatusDto $dto): MaritalStatus
    {
        return $this->maritalStatus->create([
            'title' => $dto->title,
            'description' => $dto->description,
        ])->refresh();
    }

    public function update(MaritalStatus $maritalStatus, MaritalStatusDto $dto): MaritalStatus
    {
        return tap($maritalStatus)->update([
            'title' => $dto->title,
            'description' => $dto->description,
        ]);
    }

    public function allFilter($columns = ['*'], SharedTitleFilter $filters = null)
    {
        return $this->maritalStatus
            ->select($columns)
            ->filter($filters)
            ->orderBy('title')
            ->orderBy('deleted_at')
            ->paginate()
            ->withQueryString();
    }
}
