<?php

namespace App\Repositories\Institution;

use App\DTO\Institution\SyllabusCourseModuleDto;
use App\Models\Institution\Syllabus\SyllabusCourseModule;
use App\Repositories\Base\BaseRepository;
use App\Repositories\Institution\interface\ISyllabusCourseModuleRepository;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class SyllabusCourseModuleRepository extends BaseRepository implements ISyllabusCourseModuleRepository
{
    public function __construct(protected SyllabusCourseModule $syllabusCourseModule)
    {
        parent::__construct($this->syllabusCourseModule);
    }

    public function create(SyllabusCourseModuleDto $dto): SyllabusCourseModule
    {
        return $this->syllabusCourseModule->create($this->getFields($dto))->refresh();
    }

    public function update(SyllabusCourseModule $syllabusCourseModule, SyllabusCourseModuleDto $dto): SyllabusCourseModule
    {
        $syllabusCourseModule->update($this->getFields($dto));

        return $syllabusCourseModule->refresh();
    }

    public function allByCourseSyllabus(int $courseSyllabusId): LengthAwarePaginator
    {
        return $this->syllabusCourseModule
            ->query()
            ->where('course_syllabus_id', $courseSyllabusId)
            ->orderBy('title')
            ->paginate()
            ->withQueryString();
    }

    private function getFields(SyllabusCourseModuleDto $dto): array
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
