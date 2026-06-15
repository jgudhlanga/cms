<?php

declare(strict_types=1);

namespace App\Services\Institution;

use App\Enums\AcademicCalendars\AcademicCalendarTypeEnum;
use App\Models\AcademicCalendars\AcademicYearOption;
use App\Models\Institution\DepartmentLevel;
use Illuminate\Support\Str;
use RuntimeException;

class ResolveAcademicYearOptionFromImport
{
    public function resolve(
        string $semester,
        ?int $courseSyllabusId = null,
        ?int $institutionDepartmentId = null,
        ?string $levelName = null,
    ): int {
        $semester = trim($semester);

        if ($semester === '') {
            throw new RuntimeException(__('syllabus.import_semester_required'));
        }

        $slugPrefix = $this->resolveSlugPrefix($courseSyllabusId, $institutionDepartmentId, $levelName);
        $slug = Str::slug($semester);

        $option = AcademicYearOption::query()
            ->where('slug', 'like', $slugPrefix.'-%')
            ->where(function ($query) use ($semester, $slug): void {
                $query->whereRaw('LOWER(name) = ?', [mb_strtolower($semester)])
                    ->orWhere('slug', $slug);
            })
            ->first();

        if ($option === null) {
            throw new RuntimeException(__('syllabus.import_semester_not_found', ['semester' => $semester]));
        }

        return (int) $option->id;
    }

    private function resolveSlugPrefix(
        ?int $courseSyllabusId,
        ?int $institutionDepartmentId,
        ?string $levelName,
    ): string {
        if ($courseSyllabusId !== null) {
            return app(ResolveCalendarTypeSlugPrefixFromCourseSyllabus::class)->resolve($courseSyllabusId);
        }

        if ($institutionDepartmentId !== null && trim((string) $levelName) !== '') {
            $departmentLevel = DepartmentLevel::query()
                ->where('institution_department_id', $institutionDepartmentId)
                ->whereHas('level', function ($query) use ($levelName): void {
                    $query->whereRaw('LOWER(name) = ?', [mb_strtolower(trim($levelName))]);
                })
                ->with('level')
                ->first();

            $calendarType = $departmentLevel?->level?->calendar_type;

            if ($calendarType instanceof AcademicCalendarTypeEnum) {
                return $calendarType->value;
            }

            $resolved = AcademicCalendarTypeEnum::tryFrom((string) $calendarType);

            if ($resolved !== null) {
                return $resolved->value;
            }
        }

        return AcademicCalendarTypeEnum::SEMESTER->value;
    }
}
