<?php

declare(strict_types=1);

namespace App\Services\Setup\Checks;

use App\Contracts\Setup\SetupGapCheck;
use App\Services\Setup\Checks\Concerns\ResolvesDepartments;
use App\Services\Students\IntakePeriodOrderingService;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Shared lookups for the checks that read programme configuration.
 *
 * Application-based checks look at the current admissions intake only: historical intakes carry their own
 * long-settled data and re-reporting them every night would bury the gaps that still matter.
 */
abstract class ProgrammeSetupCheck implements SetupGapCheck
{
    use ResolvesDepartments;

    public function __construct(protected readonly IntakePeriodOrderingService $intakePeriods) {}

    protected function currentIntakePeriodId(): ?int
    {
        return $this->intakePeriods->defaultAdminIntakePeriod()?->id;
    }

    /**
     * @param  list<int>  $departmentCourseIds
     * @return array<int, string>
     */
    protected function courseNames(array $departmentCourseIds): array
    {
        if ($departmentCourseIds === []) {
            return [];
        }

        return DB::table('department_courses as dc')
            ->join('courses as c', 'c.id', '=', 'dc.course_id')
            ->whereIn('dc.id', $departmentCourseIds)
            ->pluck('c.name', 'dc.id')
            ->mapWithKeys(fn (?string $name, mixed $id): array => [(int) $id => (string) $name])
            ->all();
    }

    /**
     * @param  list<int>  $departmentLevelIds
     * @return array<int, string>
     */
    protected function levelNames(array $departmentLevelIds): array
    {
        if ($departmentLevelIds === []) {
            return [];
        }

        return DB::table('department_levels as dl')
            ->join('levels as l', 'l.id', '=', 'dl.level_id')
            ->whereIn('dl.id', $departmentLevelIds)
            ->pluck('l.name', 'dl.id')
            ->mapWithKeys(fn (?string $name, mixed $id): array => [(int) $id => (string) $name])
            ->all();
    }

    /**
     * @return array<int, string>
     */
    protected function modeNames(): array
    {
        return DB::table('mode_of_studies')
            ->pluck('name', 'id')
            ->mapWithKeys(fn (?string $name, mixed $id): array => [(int) $id => (string) $name])
            ->all();
    }

    /**
     * Live mode configuration keyed by "courseId:levelId".
     *
     * @return Collection<string, list<int>>
     */
    protected function configuredModes(): Collection
    {
        return DB::table('course_level_modes')
            ->whereNull('deleted_at')
            ->select('department_course_id', 'department_level_id', 'modes')
            ->get()
            ->mapWithKeys(function (object $row): array {
                $modes = json_decode((string) ($row->modes ?? '[]'), true);
                $ids = is_array($modes)
                    ? array_values(array_filter(array_map('intval', $modes), static fn (int $id): bool => $id > 0))
                    : [];

                return ["{$row->department_course_id}:{$row->department_level_id}" => $ids];
            });
    }
}
