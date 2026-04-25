<?php

namespace App\Repositories\Institution\interface;

use App\DTO\Institution\CourseSyllabusModuleDto;
use App\Models\Institution\Syllabus\CourseSyllabusModule;
use App\Repositories\Base\Interface\IBaseRepository;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface ICourseSyllabusModuleRepository extends IBaseRepository
{
    public function create(CourseSyllabusModuleDto $dto): CourseSyllabusModule;

    public function update(CourseSyllabusModule $courseSyllabusModule, CourseSyllabusModuleDto $dto): CourseSyllabusModule;

    public function allByCourseSyllabus(int $courseSyllabusId): LengthAwarePaginator;
}
