<?php

declare(strict_types=1);

namespace App\Support\Institution;

use App\Models\AcademicCalendars\Semester;
use App\Models\Institution\Syllabus\CourseSyllabusModule;
use App\Services\Institution\ResolveCalendarTypeSlugPrefixFromCourseSyllabus;
use Illuminate\Database\Eloquent\Builder;

final class CourseSyllabusModulePeriod
{
    public static function matchesPeriod(CourseSyllabusModule $module, int $semesterId): bool
    {
        if ((int) $module->semester_id === $semesterId) {
            return true;
        }

        if (! $module->all_semesters) {
            return false;
        }

        $slugPrefix = app(ResolveCalendarTypeSlugPrefixFromCourseSyllabus::class)
            ->resolve((int) $module->course_syllabus_id);

        $optionSlug = Semester::query()
            ->whereKey($semesterId)
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
        int $semesterId,
        string $slugPrefix,
    ): Builder {
        return $query->where(function (Builder $periodQuery) use ($semesterId, $slugPrefix): void {
            $periodQuery
                ->where('semester_id', $semesterId)
                ->orWhere(function (Builder $allSemestersQuery) use ($slugPrefix): void {
                    $allSemestersQuery
                        ->where('all_semesters', true)
                        ->whereHas('semester', function (Builder $optionQuery) use ($slugPrefix): void {
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
