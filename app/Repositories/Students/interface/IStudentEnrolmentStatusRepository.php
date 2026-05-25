<?php

namespace App\Repositories\Students\interface;

use App\DTO\Students\StudentEnrolmentStatusDto;
use App\Http\Filters\Shared\SharedNameFilter;
use App\Models\Students\StudentEnrolmentStatus;
use App\Repositories\Base\Interface\IBaseRepository;
use Illuminate\Database\Eloquent\Model;

interface IStudentEnrolmentStatusRepository extends IBaseRepository
{
    public function create(StudentEnrolmentStatusDto $dto): Model;

    public function update(StudentEnrolmentStatus $status, StudentEnrolmentStatusDto $dto): StudentEnrolmentStatus;

    public function allFilter($columns = ['*'], ?SharedNameFilter $filters = null);
}
