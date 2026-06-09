<?php

namespace App\Repositories\Institution\interface;

use App\DTO\Institution\CreateStaffDto;
use App\DTO\Institution\StaffImportRowDto;
use App\Http\Filters\Institution\StaffFilter;
use App\Models\Institution\Staff;
use App\Repositories\Base\Interface\IBaseRepository;

interface IStaffRepository extends IBaseRepository
{
    public function create(CreateStaffDto $dto);

    public function upsertFromImport(StaffImportRowDto $dto): Staff;

    public function update(Staff $staff, CreateStaffDto $dto);

    public function allFilter($columns = ['*'], ?StaffFilter $filters = null);
}
