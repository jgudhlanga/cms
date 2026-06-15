<?php

namespace App\Repositories\Institution;

use App\DTO\Institution\CourseSyllabusDto;
use App\Models\Institution\Syllabus\CourseSyllabus;
use App\Repositories\Base\BaseRepository;
use App\Repositories\Institution\interface\ICourseSyllabusRepository;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Model;

class CourseSyllabusRepository extends BaseRepository implements ICourseSyllabusRepository
{
    public function __construct(protected CourseSyllabus $courseSyllabus)
    {
        parent::__construct($this->courseSyllabus);
    }

    public function create(CourseSyllabusDto $dto): CourseSyllabus
    {
        return $this->courseSyllabus->create($this->getFields($dto))->refresh();
    }

    public function update(CourseSyllabus $courseSyllabus, CourseSyllabusDto $dto): CourseSyllabus
    {
        $courseSyllabus->update($this->getFields($dto));

        return $courseSyllabus->refresh();
    }

    public function delete(Model $model, bool $force = false): ?bool
    {
        if ($model instanceof CourseSyllabus) {
            $model->clearMediaCollection(CourseSyllabus::MEDIA_COLLECTION_SYLLABUS_DOCUMENT);
        }

        return parent::delete($model, $force);
    }

    public function allByInstitutionDepartment(int $institutionDepartmentId): LengthAwarePaginator
    {
        return $this->courseSyllabus
            ->query()
            ->with(['departmentLevelCourse.departmentLevel.level', 'departmentLevelCourse.departmentCourse.course', 'syllabusDocument'])
            ->withCount('syllabusCourseModules')
            ->where('institution_department_id', $institutionDepartmentId)
            ->orderBy('title')
            ->paginate()
            ->withQueryString();
    }

    private function getFields(CourseSyllabusDto $dto): array
    {
        return [
            'institution_department_id' => $dto->institution_department_id,
            'department_level_course_id' => $dto->department_level_course_id,
            'title' => $dto->title,
            'code' => $dto->code,
            'implementation_year' => $dto->implementation_year,
            'status' => $dto->status,
        ];
    }
}
