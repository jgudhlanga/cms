<?php

namespace App\Repositories\Students;

use App\DTO\Students\StudentProgramDto;
use App\Http\Filters\Students\StudentProgramFilter;
use App\Models\Students\StudentProgram;
use App\Repositories\Base\BaseRepository;
use Illuminate\Database\Eloquent\Model;

class StudentProgramRepository extends BaseRepository implements interface\IStudentProgramRepository
{

    public function __construct(
        protected StudentProgram $studentProgram,
    )
    {
        parent::__construct($this->studentProgram);
    }

    public function create(StudentProgramDto $dto): Model
    {
        return $this->studentProgram->create($this->getFields($dto))->refresh();
    }

    public function update(StudentProgram $studentProgram, StudentProgramDto $dto)
    {
        return tap($studentProgram)->update($this->getFields($dto));
    }

    public function allFilter($columns = ['*'], ?StudentProgramFilter $filters = null)
    {
        return $this->studentProgram
            ->select($columns)
            ->filter($filters)
            ->orderBy('created_at')
            ->orderBy('deleted_at')
            ->paginate()
            ->withQueryString();
    }

    private function getFields(StudentProgramDto $dto): array
    {
        return [
            'student_id' => $dto->student_id,
            'mode_of_study_id' => $dto->mode_of_study_id,
            'institution_department_id' => $dto->institution_department_id,
            'department_level_id' => $dto->department_level_id,
            'department_course_id' => $dto->department_course_id,
            'required_level_completed' => $dto->required_level_completed,
            'read_write_acknowledged' => $dto->read_write_acknowledged,
            'intake_period_id' => $dto->intake_period_id,
        ];
    }
}
