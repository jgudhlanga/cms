<?php

declare(strict_types=1);

namespace App\Services\Setup\Checks;

use App\Contracts\Setup\SetupGapCheck;
use App\Enums\Setup\SetupGapCheckEnum;
use App\Models\AcademicCalendars\AcademicCalendar;
use App\Services\Setup\Checks\Concerns\ResolvesDepartments;
use App\Support\Setup\SetupGapResult;
use Illuminate\Support\Facades\DB;

/**
 * Open classes in the current calendar year with nobody assigned as lecturer in charge, so coursework
 * capture for them has no owner.
 */
class ClassConfigWithoutLecturerInChargeCheck implements SetupGapCheck
{
    use ResolvesDepartments;

    public function key(): SetupGapCheckEnum
    {
        return SetupGapCheckEnum::CLASS_CONFIG_WITHOUT_LECTURER_IN_CHARGE;
    }

    /**
     * @return iterable<SetupGapResult>
     */
    public function run(): iterable
    {
        $calendarYear = (string) AcademicCalendar::resolveSemesterForDate()->calendar_year;

        $rows = DB::table('class_configs as cc')
            ->where('cc.calendar_year', $calendarYear)
            ->where('cc.status', 'open')
            ->whereNotNull('cc.institution_department_id')
            ->whereNotExists(function ($query): void {
                $query->selectRaw('1')
                    ->from('class_config_lecturers_in_charge as lic')
                    ->whereColumn('lic.class_config_id', 'cc.id');
            })
            ->select(['cc.id', 'cc.name', 'cc.institution_department_id'])
            ->get();

        if ($rows->isEmpty()) {
            return [];
        }

        $departments = $this->departments(
            $rows->pluck('institution_department_id')->map(fn ($id): int => (int) $id)->unique()->values()->all(),
        );

        $gaps = [];

        foreach ($rows as $row) {
            $departmentId = (int) $row->institution_department_id;
            $department = $departments[$departmentId] ?? null;

            if ($department === null) {
                continue;
            }

            $gaps[] = new SetupGapResult(
                check: $this->key(),
                tenantId: $department['tenantId'],
                title: __('setup_gaps.class_config_without_lecturer_in_charge_title', [
                    'class' => (string) ($row->name ?? $row->id),
                ]),
                body: __('setup_gaps.class_config_without_lecturer_in_charge_body'),
                institutionDepartmentId: $departmentId,
                url: $this->departmentClassesUrl($departmentId, $calendarYear),
                meta: [
                    'classConfigId' => (int) $row->id,
                    'calendarYear' => $calendarYear,
                ],
                fingerprintKey: "class-config:{$row->id}",
            );
        }

        return $gaps;
    }
}
