<?php

namespace App\Repositories\Institution;

use App\DTO\Institution\CourseSyllabusModuleDto;
use App\Models\Institution\Syllabus\CourseSyllabusModule;
use App\Repositories\Base\BaseRepository;
use App\Repositories\Institution\interface\ICourseSyllabusModuleRepository;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class CourseSyllabusModuleRepository extends BaseRepository implements ICourseSyllabusModuleRepository
{
    public function __construct(protected CourseSyllabusModule $courseSyllabusModule)
    {
        parent::__construct($this->courseSyllabusModule);
    }

    public function create(CourseSyllabusModuleDto $dto): CourseSyllabusModule
    {
        return $this->courseSyllabusModule->create($this->getFields($dto))->refresh();
    }

    public function update(CourseSyllabusModule $courseSyllabusModule, CourseSyllabusModuleDto $dto): CourseSyllabusModule
    {
        $courseSyllabusModule->update($this->getFields($dto));

        return $courseSyllabusModule->refresh();
    }

    public function allByCourseSyllabus(int $courseSyllabusId): LengthAwarePaginator
    {
        return $this->courseSyllabusModule
            ->query()
            ->where('course_syllabus_id', $courseSyllabusId)
            ->orderBy('title')
            ->paginate()
            ->withQueryString();
    }

    private function getFields(CourseSyllabusModuleDto $dto): array
    {
        return [
            'course_syllabus_id' => $dto->course_syllabus_id,
            'title' => $dto->title,
            'code' => $dto->code,
            'duration_in_hours' => $dto->duration_in_hours,
            'nql_level' => $dto->nql_level,
            'prerequisite_module_ids' => $dto->prerequisite_module_ids,
            'shared' => $dto->shared,
        ];
    }
}
