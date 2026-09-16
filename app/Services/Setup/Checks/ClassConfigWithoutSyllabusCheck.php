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
 * Open classes with no modules attached: there is nothing to capture marks against until a syllabus is
 * linked. course_syllabus_ids is a json array, so "empty" covers null, '[]' and an array of nothing.
 */
class ClassConfigWithoutSyllabusCheck implements SetupGapCheck
{
    use ResolvesDepartments;

    public function key(): SetupGapCheckEnum
    {
        return SetupGapCheckEnum::CLASS_CONFIG_WITHOUT_SYLLABUS;
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
            ->select(['cc.id', 'cc.name', 'cc.institution_department_id', 'cc.course_syllabus_ids'])
            ->get()
            ->filter(function (object $row): bool {
                $ids = json_decode((string) ($row->course_syllabus_ids ?? '[]'), true);

                return ! is_array($ids) || $ids === [];
            })
            ->values();

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
                title: __('setup_gaps.class_config_without_syllabus_title', [
                    'class' => (string) ($row->name ?? $row->id),
                ]),
                body: __('setup_gaps.class_config_without_syllabus_body'),
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
