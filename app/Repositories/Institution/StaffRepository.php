<?php

namespace App\Repositories\Institution;

use App\DTO\Institution\CreateStaffDto;
use App\Http\Filters\Institution\StaffFilter;
use App\Models\Institution\Staff;
use App\Repositories\Base\BaseRepository;
use App\Repositories\Institution\interface\IStaffRepository;

class StaffRepository extends BaseRepository implements IStaffRepository
{
    public function __construct(protected Staff $staff)
    {
        parent::__construct($this->staff);
    }

    public function create(CreateStaffDto $dto): Staff
    {
        return $this->subject->create($this->getFields($dto))->refresh();
    }

    public function update(Staff $staff, CreateStaffDto $dto): Staff
    {
        return tap($staff)->update($this->getFields($dto))->refresh();
    }

    public function allFilter($columns = ['*'], StaffFilter $filters = null)
    {
        return $this->subject
            ->select($columns)
            ->filter($filters)
            ->orderBy('created_at')
            ->orderBy('deleted_at')
            ->paginate()
            ->withQueryString();
    }

    private function getFields(CreateStaffDto $dto): array
    {
        return [
            'name' => $dto->name,
            'description' => $dto->description,
        ];
    }
}
