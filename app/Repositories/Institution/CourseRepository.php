<?php

namespace App\Repositories\Institution;


use App\DTO\Institution\CourseDto;
use App\Http\Filters\Shared\SharedNameFilter;
use App\Models\Institution\Course;
use App\Repositories\Base\BaseRepository;
use App\Repositories\Institution\interface\ICourseRepository;

class CourseRepository extends BaseRepository implements ICourseRepository
{
    public function __construct(protected Course $course)
    {
        parent::__construct($this->course);
    }

    public function create(CourseDto $dto): Course
    {
        return $this->course->create([
            'name' => $dto->name,
            'description' => $dto->description,
        ])->refresh();
    }

    public function update(Course $course, CourseDto $dto): Course
    {
        return tap($course)->update([
            'name' => $dto->name,
            'description' => $dto->description,
        ]);
    }

    public function allFilter($columns = ['*'], SharedNameFilter $filters = null)
    {
        return $this->course
            ->select($columns)
            ->filter($filters)
           #->orderBy('description')
            ->orderBy('position')
            ->orderBy('name')
            ->orderBy('deleted_at')
            ->paginate()
            ->withQueryString();
    }
}
