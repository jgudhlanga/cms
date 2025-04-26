<?php

namespace App\Repositories\Courses\interface;

use App\DTO\Institution\CourseDto;
use App\Http\Filters\Shared\SharedNameFilter;
use App\Models\Institution\Course;
use App\Repositories\Base\Interface\IBaseRepository;

interface ICourseRepository extends IBaseRepository
{
    public function create(CourseDto $dto);

    public function update(Course $course, CourseDto $dto);

    public function allFilter($columns = ['*'], SharedNameFilter $filters = null);
}
