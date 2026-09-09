<?php

declare(strict_types=1);

namespace App\Services\Institution\Reconciliation;

use App\Models\Institution\DepartmentCourse;
use App\Models\Institution\DepartmentLevel;
use App\Models\Institution\DepartmentLevelCourse;
use App\Models\Institution\InstitutionDepartment;
use App\Models\Institution\ProgrammeSemester;
use App\Support\Institution\ProgrammeSemesterNameFormatter;
use Illuminate\Support\Collection;

class DepartmentReconciliationOfferingMatcher
{
    public function normalize(?string $value): string
    {
        $normalized = strtoupper(trim((string) $value));
        $normalized = str_replace(['_', '.', '-'], ' ', $normalized);
        $normalized = preg_replace('/\s+/', ' ', $normalized) ?? $normalized;

        return trim($normalized);
    }

    public function findDepartmentLevel(InstitutionDepartment $department, ?string $levelName): ?DepartmentLevel
    {
        $needle = $this->normalize($levelName);

        if ($needle === '') {
            return null;
        }

        return DepartmentLevel::query()
            ->where('institution_department_id', $department->id)
            ->with('level')
            ->get()
            ->first(fn (DepartmentLevel $row): bool => $this->normalize($row->level?->name) === $needle);
    }

    public function findDepartmentCourse(InstitutionDepartment $department, ?string $courseName): ?DepartmentCourse
    {
        $needle = $this->normalize($courseName);

        if ($needle === '') {
            return null;
        }

        return DepartmentCourse::query()
            ->where('institution_department_id', $department->id)
            ->with('course')
            ->get()
            ->first(function (DepartmentCourse $row) use ($needle): bool {
                $name = $this->normalize($row->course?->name);
                $slug = $this->normalize($row->course?->slug);

                return $name === $needle || $slug === $needle;
            });
    }

    public function findProgrammeSemester(DepartmentLevelCourse $offering, ?string $phaseName): ?ProgrammeSemester
    {
        $needle = $this->normalize($phaseName);

        if ($needle === '') {
            return null;
        }

        $offering->loadMissing(['programmeSemesters', 'departmentLevel.level']);

        /** @var Collection<int, ProgrammeSemester> $phases */
        $phases = $offering->programmeSemesters ?? collect();

        return $phases->first(function (ProgrammeSemester $phase) use ($needle, $offering): bool {
            $candidates = [
                $this->normalize($phase->name),
                $this->normalize(ProgrammeSemesterNameFormatter::shortName($phase->name)),
                $this->normalize(ProgrammeSemesterNameFormatter::qualifiedName(
                    $offering->departmentLevel?->level?->name,
                    $phase->name,
                )),
                $this->normalize(ProgrammeSemesterNameFormatter::qualifiedName(
                    $offering->departmentLevel?->level?->name,
                    $phase->name,
                    true,
                )),
            ];

            return in_array($needle, $candidates, true);
        });
    }
}
