<?php

namespace App\Repositories\Institution\interface;

use App\DTO\Institution\SyllabusCourseModuleDto;
use App\Models\Institution\Syllabus\SyllabusCourseModule;
use App\Repositories\Base\Interface\IBaseRepository;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface ISyllabusCourseModuleRepository extends IBaseRepository
{
    public function create(SyllabusCourseModuleDto $dto): SyllabusCourseModule;

    public function update(SyllabusCourseModule $syllabusCourseModule, SyllabusCourseModuleDto $dto): SyllabusCourseModule;

    public function allByCourseSyllabus(int $courseSyllabusId): LengthAwarePaginator;
}
