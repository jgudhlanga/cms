<?php

declare(strict_types=1);

namespace App\Support\Institution;

use App\Models\AcademicCalendars\Semester;
use App\Models\Institution\Syllabus\CourseSyllabusModule;
use App\Services\Institution\ResolveCalendarTypeSlugPrefixFromCourseSyllabus;
use Illuminate\Database\Eloquent\Builder;

final class CourseSyllabusModulePeriod
{
    public static function matchesPeriod(CourseSyllabusModule $module, int $semesterId, ?int $programmeSemesterId = null): bool
    {
        if ($module->all_semesters) {
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

        if ($programmeSemesterId !== null) {
            if ($module->programme_semester_id !== null) {
                return (int) $module->programme_semester_id === $programmeSemesterId;
            }

            // Legacy modules without a programme-semester pin match the calendar half only.
            return (int) $module->semester_id === $semesterId;
        }

        return (int) $module->semester_id === $semesterId;
    }

    /**
     * @param  Builder<CourseSyllabusModule>  $query
     * @return Builder<CourseSyllabusModule>
     */
    public static function scopeForPeriod(
        Builder $query,
        int $semesterId,
        string $slugPrefix,
        ?int $programmeSemesterId = null,
    ): Builder {
        return $query->where(function (Builder $periodQuery) use ($semesterId, $slugPrefix, $programmeSemesterId): void {
            $periodQuery->where(function (Builder $allSemestersQuery) use ($slugPrefix): void {
                $allSemestersQuery
                    ->where('all_semesters', true)
                    ->whereHas('semester', function (Builder $optionQuery) use ($slugPrefix): void {
                        $optionQuery->where('slug', 'like', $slugPrefix.'-%');
                    });
            });

            if ($programmeSemesterId !== null) {
                $periodQuery
                    ->orWhere('programme_semester_id', $programmeSemesterId)
                    ->orWhere(function (Builder $legacyQuery) use ($semesterId): void {
                        $legacyQuery
                            ->whereNull('programme_semester_id')
                            ->where('semester_id', $semesterId);
                    });

                return;
            }

            $periodQuery->orWhere('semester_id', $semesterId);
        });
    }

    public static function slugPrefixForSyllabus(int $courseSyllabusId): string
    {
        return app(ResolveCalendarTypeSlugPrefixFromCourseSyllabus::class)->resolve($courseSyllabusId);
    }
}
