<?php

use App\Models\AcademicCalendars\AcademicCalendar;
use App\Models\AcademicCalendars\AcademicCalendarClass;
use App\Models\AcademicCalendars\ClassConfig;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('class_configs', function (Blueprint $table): void {
            $table->string('calendar_year')->nullable()->after('institution_department_id');
        });

        ClassConfig::query()->withTrashed()->orderBy('id')->each(function (ClassConfig $config): void {
            $year = AcademicCalendar::query()->whereKey($config->getAttribute('academic_calendar_id'))->value('calendar_year');
            if ($year !== null && $year !== '') {
                $config->newQuery()->whereKey($config->id)->update(['calendar_year' => $year]);
            }
        });

        DB::table('class_configs')->whereNull('calendar_year')->update(['calendar_year' => 'legacy']);

        $this->mergeDuplicateClassConfigs();

        Schema::table('class_configs', function (Blueprint $table): void {
            $table->dropForeign(['academic_calendar_id']);
            $table->dropColumn('academic_calendar_id');
        });

        Schema::table('class_configs', function (Blueprint $table): void {
            $table->unique(
                ['institution_department_id', 'department_course_id', 'department_level_id', 'mode_of_study_id', 'calendar_year'],
                'class_configs_dept_course_level_mode_year_unique'
            );
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('class_configs', function (Blueprint $table): void {
            $table->dropUnique('class_configs_dept_course_level_mode_year_unique');
        });

        Schema::table('class_configs', function (Blueprint $table): void {
            $table->foreignId('academic_calendar_id')->nullable()->after('institution_department_id')->constrained();
        });

        ClassConfig::query()->withTrashed()->orderBy('id')->each(function (ClassConfig $config): void {
            $calendarId = AcademicCalendar::query()
                ->where('calendar_year', $config->getAttribute('calendar_year'))
                ->orderByDesc('opening_date')
                ->value('id');
            if ($calendarId !== null) {
                $config->newQuery()->whereKey($config->id)->update(['academic_calendar_id' => $calendarId]);
            }
        });

        Schema::table('class_configs', function (Blueprint $table): void {
            $table->dropColumn('calendar_year');
        });
    }

    private function mergeDuplicateClassConfigs(): void
    {
        $groups = DB::table('class_configs')
            ->select([
                'institution_department_id',
                'department_course_id',
                'department_level_id',
                'mode_of_study_id',
                'calendar_year',
                DB::raw('COUNT(*) as cnt'),
                DB::raw('MIN(id) as min_id'),
            ])
            ->whereNull('deleted_at')
            ->whereNotNull('calendar_year')
            ->groupBy(
                'institution_department_id',
                'department_course_id',
                'department_level_id',
                'mode_of_study_id',
                'calendar_year',
            )
            ->having('cnt', '>', 1)
            ->get();

        foreach ($groups as $group) {
            $ids = ClassConfig::query()
                ->withTrashed()
                ->where('institution_department_id', $group->institution_department_id)
                ->where('department_course_id', $group->department_course_id)
                ->where('department_level_id', $group->department_level_id)
                ->where('mode_of_study_id', $group->mode_of_study_id)
                ->where('calendar_year', $group->calendar_year)
                ->orderByRaw('CASE WHEN students_per_class IS NULL THEN 1 ELSE 0 END ASC')
                ->orderByDesc('students_per_class')
                ->orderByDesc('id')
                ->pluck('id')
                ->all();

            if (count($ids) < 2) {
                continue;
            }

            $keeperId = (int) array_shift($ids);

            foreach ($ids as $duplicateId) {
                AcademicCalendarClass::query()
                    ->where('class_config_id', $duplicateId)
                    ->update(['class_config_id' => $keeperId]);

                ClassConfig::query()->withTrashed()->whereKey($duplicateId)->forceDelete();
            }
        }
    }
};
