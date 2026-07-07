<?php

namespace App\Repositories\Institution;

use App\DTO\Institution\CourseSyllabusModuleDto;
use App\Models\Institution\Syllabus\CourseSyllabusModule;
use App\Repositories\Base\BaseRepository;
use App\Repositories\Institution\interface\ICourseSyllabusModuleRepository;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class CourseSyllabusModuleRepository extends BaseRepository implements ICourseSyllabusModuleRepository
{
    public function __construct(protected CourseSyllabusModule $courseSyllabusModule)
    {
        parent::__construct($this->courseSyllabusModule);
    }

    public function create(CourseSyllabusModuleDto $dto): CourseSyllabusModule
    {
        return DB::transaction(function () use ($dto): CourseSyllabusModule {
            $module = $this->courseSyllabusModule->create($this->getFields($dto))->refresh();
            $this->syncLecturers($module, $dto->staff_ids);

            return $module->refresh();
        });
    }

    public function update(CourseSyllabusModule $courseSyllabusModule, CourseSyllabusModuleDto $dto): CourseSyllabusModule
    {
        return DB::transaction(function () use ($courseSyllabusModule, $dto): CourseSyllabusModule {
            $courseSyllabusModule->update($this->getFields($dto));
            $this->syncLecturers($courseSyllabusModule, $dto->staff_ids);

            return $courseSyllabusModule->refresh();
        });
    }

    public function allByCourseSyllabus(int $courseSyllabusId): LengthAwarePaginator
    {
        return $this->courseSyllabusModule
            ->query()
            ->with(['academicYearOption', 'lecturers.user'])
            ->where('course_syllabus_modules.course_syllabus_id', $courseSyllabusId)
            ->join(
                'academic_year_options',
                'academic_year_options.id',
                '=',
                'course_syllabus_modules.academic_year_option_id',
            )
            ->orderBy('academic_year_options.name')
            ->orderBy('course_syllabus_modules.title')
            ->select('course_syllabus_modules.*')
            ->paginate()
            ->withQueryString();
    }

    private function getFields(CourseSyllabusModuleDto $dto): array
    {
        return [
            'course_syllabus_id' => $dto->course_syllabus_id,
            'academic_year_option_id' => $dto->academic_year_option_id,
            'title' => $dto->title,
            'code' => $dto->code,
            'duration_in_hours' => $dto->duration_in_hours,
            'nql_level' => $dto->nql_level,
            'prerequisite_module_ids' => $dto->prerequisite_module_ids,
            'shared' => $dto->shared,
            'all_semesters' => $dto->all_semesters,
            'capture_mark_only' => $dto->capture_mark_only,
        ];
    }

    /** @param array<int> $staffIds */
    private function syncLecturers(CourseSyllabusModule $module, array $staffIds): void
    {
        DB::table('course_syllabus_module_lecturers')
            ->where('course_syllabus_module_id', $module->id)
            ->whereNull('academic_calendar_class_id')
            ->delete();

        $now = now();

        foreach (array_values(array_unique(array_map('intval', $staffIds))) as $staffId) {
            DB::table('course_syllabus_module_lecturers')->insert([
                'tenant_id' => $module->tenant_id,
                'course_syllabus_module_id' => $module->id,
                'staff_id' => $staffId,
                'academic_calendar_class_id' => null,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }
    }
}
