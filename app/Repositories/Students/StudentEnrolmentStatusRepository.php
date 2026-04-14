<?php

namespace App\Repositories\Students;

use App\DTO\Students\StudentEnrolmentStatusDto;
use App\Http\Filters\Shared\SharedNameFilter;
use App\Models\Students\StudentEnrolmentStatus;
use App\Repositories\Base\BaseRepository;
use App\Repositories\Students\interface\IStudentEnrolmentStatusRepository;
use Illuminate\Database\Eloquent\Model;

class StudentEnrolmentStatusRepository extends BaseRepository implements IStudentEnrolmentStatusRepository
{
    public function __construct(protected StudentEnrolmentStatus $status)
    {
        parent::__construct($this->status);
    }

    public function create(StudentEnrolmentStatusDto $dto): Model
    {
        return $this->status->create($this->getFields($dto))->refresh();
    }

    public function update(StudentEnrolmentStatus $status, StudentEnrolmentStatusDto $dto): StudentEnrolmentStatus
    {
        return tap($status)->update($this->getFields($dto));
    }

    public function allFilter($columns = ['*'], ?SharedNameFilter $filters = null)
    {
        return $this->status
            ->select($columns)
            ->filter($filters)
            ->orderBy('name')
            ->orderBy('deleted_at')
            ->paginate()
            ->withQueryString();
    }

    private function getFields(StudentEnrolmentStatusDto $dto): array
    {
        return [
            'name' => $dto->name,
            'description' => $dto->description,
            'color' => $dto->color,
        ];
    }
}
