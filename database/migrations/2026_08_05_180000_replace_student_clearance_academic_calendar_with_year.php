<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('student_clearances')) {
            return;
        }

        if (Schema::hasColumn('student_clearances', 'calendar_year')
            && ! Schema::hasColumn('student_clearances', 'academic_calendar_id')) {
            $this->ensureUniqueIndex();

            return;
        }

        if (! Schema::hasColumn('student_clearances', 'calendar_year')) {
            Schema::table('student_clearances', function (Blueprint $table): void {
                $table->unsignedSmallInteger('calendar_year')->default(0)->after('student_id');
            });
        }

        if (Schema::hasColumn('student_clearances', 'academic_calendar_id')) {
            $rows = DB::table('student_clearances')
                ->leftJoin('academic_calendars', 'academic_calendars.id', '=', 'student_clearances.academic_calendar_id')
                ->select([
                    'student_clearances.id',
                    'academic_calendars.calendar_year as source_year',
                ])
                ->get();

            foreach ($rows as $row) {
                $year = $this->parseCalendarYear($row->source_year ?? null) ?? (int) date('Y');
                DB::table('student_clearances')->where('id', $row->id)->update(['calendar_year' => $year]);
            }

            // MySQL: student_id FK often depends on the composite unique index.
            // Add a dedicated index first so we can drop the unique safely.
            if (! Schema::hasIndex('student_clearances', 'student_clearances_student_id_index')) {
                Schema::table('student_clearances', function (Blueprint $table): void {
                    $table->index('student_id', 'student_clearances_student_id_index');
                });
            }

            if ($this->hasForeignKeyOnColumn('student_clearances', 'academic_calendar_id')) {
                Schema::table('student_clearances', function (Blueprint $table): void {
                    $table->dropForeign(['academic_calendar_id']);
                });
            }

            if (Schema::hasIndex('student_clearances', 'student_clearances_unique')) {
                Schema::table('student_clearances', function (Blueprint $table): void {
                    $table->dropUnique('student_clearances_unique');
                });
            }

            // Drop leftover academic_calendar_id index from foreignId() if still present.
            if (Schema::hasIndex('student_clearances', 'student_clearances_academic_calendar_id_foreign')) {
                Schema::table('student_clearances', function (Blueprint $table): void {
                    $table->dropIndex('student_clearances_academic_calendar_id_foreign');
                });
            }

            Schema::table('student_clearances', function (Blueprint $table): void {
                $table->dropColumn('academic_calendar_id');
            });
        }

        $this->ensureUniqueIndex();
    }

    public function down(): void
    {
        if (! Schema::hasTable('student_clearances')
            || ! Schema::hasColumn('student_clearances', 'calendar_year')) {
            return;
        }

        if (Schema::hasIndex('student_clearances', 'student_clearances_unique')) {
            Schema::table('student_clearances', function (Blueprint $table): void {
                $table->dropUnique('student_clearances_unique');
            });
        }

        Schema::table('student_clearances', function (Blueprint $table): void {
            $table->foreignId('academic_calendar_id')->nullable()->after('student_id')->constrained('academic_calendars');
            $table->dropColumn('calendar_year');
            $table->unique(
                ['student_id', 'academic_calendar_id', 'semester_id'],
                'student_clearances_unique'
            );
        });
    }

    private function ensureUniqueIndex(): void
    {
        if (Schema::hasIndex('student_clearances', 'student_clearances_unique')) {
            return;
        }

        Schema::table('student_clearances', function (Blueprint $table): void {
            $table->unique(
                ['student_id', 'calendar_year', 'semester_id'],
                'student_clearances_unique'
            );
        });
    }

    private function hasForeignKeyOnColumn(string $table, string $column): bool
    {
        foreach (Schema::getForeignKeys($table) as $foreignKey) {
            $columns = $foreignKey['columns'] ?? [];
            if (in_array($column, $columns, true)) {
                return true;
            }
        }

        return false;
    }

    private function parseCalendarYear(mixed $value): ?int
    {
        if (is_numeric($value)) {
            return (int) $value;
        }

        if (! is_string($value) || $value === '') {
            return null;
        }

        if (preg_match_all('/(20\d{2}|19\d{2})/', $value, $matches) >= 1) {
            $years = array_map('intval', $matches[1]);

            return max($years);
        }

        return null;
    }
};
