<?php

namespace App\Repositories\Students;

use App\DTO\Students\StudentApplicationDto;
use App\DTO\Students\ProgramDto;
use App\Helpers\Helper;
use App\Http\Filters\Students\StudentApplicationFilter;
use App\Models\Students\StudentApplication;
use App\Repositories\Base\BaseRepository;
use Illuminate\Database\Eloquent\Model;

class StudentApplicationRepository extends BaseRepository implements interface\IStudentApplicationRepository
{

    public function __construct(
        protected StudentApplication $studentApplication,
    )
    {
        parent::__construct($this->studentApplication);
    }

    public function create(StudentApplicationDto $dto): Model
    {
        return $this->studentApplication->create($this->getFields($dto))->refresh();
    }

    public function update(StudentApplication $studentApplication, ProgramDto $dto)
    {
        return tap($studentApplication)->update($this->getUpdateFields($dto));
    }

    public function allFilter($columns = ['*'], ?StudentApplicationFilter $filters = null)
    {
        $isDepartmentUser = Helper::isDepartmentUser();
        $userDepartments = Helper::resolveUserDepartments();
        if ($isDepartmentUser && empty($userDepartments)) {
            return collect();
        }
        $query = $this->studentApplication->select($columns)->filter($filters);
        if (!empty($userDepartments)) {
            $query->whereIn('institution_department_id', $userDepartments);
        }
        return $query->orderBy('created_at')
            ->orderBy('deleted_at')
            ->paginate()
            ->withQueryString();
    }

    private function getFields(StudentApplicationDto $dto): array
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

    private function getUpdateFields(ProgramDto $dto): array
    {
        return [
            'mode_of_study_id' => $dto->mode_of_study_id,
            'institution_department_id' => $dto->institution_department_id,
            'department_level_id' => $dto->department_level_id,
            'department_course_id' => $dto->department_course_id,
            'required_level_completed' => $dto->required_level_completed,
            'read_write_acknowledged' => $dto->read_write_acknowledged,
        ];
    }
}
