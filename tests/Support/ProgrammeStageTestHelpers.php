<?php

use App\Models\Institution\ProgrammeSemester;
use Illuminate\Support\Collection;

if (! function_exists('programmeSemesterAt')) {
    /**
     * @param  Collection<int, ProgrammeSemester>|iterable<ProgrammeSemester>  $semesters
     */
    function programmeSemesterAt(iterable $semesters, int $year, int $period): ?ProgrammeSemester
    {
        $match = collect($semesters)->first(
            fn (ProgrammeSemester $semester): bool => (int) $semester->year_number === $year
                && (int) $semester->period_in_year === $period,
        );

        return $match instanceof ProgrammeSemester ? $match : null;
    }
}
