<?php

declare(strict_types=1);

namespace App\Support\Institution;

use App\Models\AcademicCalendars\AcademicYearOption;
use App\Models\Institution\Syllabus\CourseSyllabusModule;
use App\Services\Institution\ResolveCalendarTypeSlugPrefixFromCourseSyllabus;
use Illuminate\Database\Eloquent\Builder;

final class CourseSyllabusModulePeriod
{
    public static function matchesPeriod(CourseSyllabusModule $module, int $academicYearOptionId): bool
    {
        if ((int) $module->academic_year_option_id === $academicYearOptionId) {
            return true;
        }

        if (! $module->all_semesters) {
            return false;
        }

        $slugPrefix = app(ResolveCalendarTypeSlugPrefixFromCourseSyllabus::class)
            ->resolve((int) $module->course_syllabus_id);

        $optionSlug = AcademicYearOption::query()
            ->whereKey($academicYearOptionId)
            ->value('slug');

        if (! is_string($optionSlug)) {
            return false;
        }

        return str_starts_with($optionSlug, $slugPrefix.'-');
    }

    /**
     * @param  Builder<CourseSyllabusModule>  $query
     * @return Builder<CourseSyllabusModule>
     */
    public static function scopeForPeriod(
        Builder $query,
        int $academicYearOptionId,
        string $slugPrefix,
    ): Builder {
        return $query->where(function (Builder $periodQuery) use ($academicYearOptionId, $slugPrefix): void {
            $periodQuery
                ->where('academic_year_option_id', $academicYearOptionId)
                ->orWhere(function (Builder $allSemestersQuery) use ($slugPrefix): void {
                    $allSemestersQuery
                        ->where('all_semesters', true)
                        ->whereHas('academicYearOption', function (Builder $optionQuery) use ($slugPrefix): void {
                            $optionQuery->where('slug', 'like', $slugPrefix.'-%');
                        });
                });
        });
    }

    public static function slugPrefixForSyllabus(int $courseSyllabusId): string
    {
        return app(ResolveCalendarTypeSlugPrefixFromCourseSyllabus::class)->resolve($courseSyllabusId);
    }
}
