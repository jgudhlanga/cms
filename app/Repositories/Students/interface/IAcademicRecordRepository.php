<?php

namespace App\Repositories\Students\interface;

use App\DTO\Students\AcademicRecordDto;
use App\Models\Students\AcademicRecord;
use App\Repositories\Base\Interface\IBaseRepository;

interface IAcademicRecordRepository extends IBaseRepository
{
    public function create(AcademicRecordDto $dto);

    public function update(AcademicRecord $academicRecord, AcademicRecordDto $dto);

    public function allFilter($columns = ['*']);
}
