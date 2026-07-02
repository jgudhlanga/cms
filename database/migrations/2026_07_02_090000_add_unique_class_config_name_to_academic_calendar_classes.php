<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $duplicateGroups = DB::table('academic_calendar_classes')
            ->select('class_config_id', 'name', DB::raw('COUNT(*) as duplicate_count'))
            ->groupBy('class_config_id', 'name')
            ->having('duplicate_count', '>', 1)
            ->get();

        foreach ($duplicateGroups as $group) {
            $rows = DB::table('academic_calendar_classes')
                ->where('class_config_id', $group->class_config_id)
                ->where('name', $group->name)
                ->orderBy('id')
                ->get(['id', 'name']);

            foreach ($rows->skip(1) as $row) {
                DB::table('academic_calendar_classes')
                    ->where('id', $row->id)
                    ->update(['name' => $row->name.'-'.$row->id]);
            }
        }

        Schema::table('academic_calendar_classes', function (Blueprint $table): void {
            $table->unique(['class_config_id', 'name'], 'academic_calendar_classes_config_name_unique');
        });
    }

    public function down(): void
    {
        Schema::table('academic_calendar_classes', function (Blueprint $table): void {
            $table->dropUnique('academic_calendar_classes_config_name_unique');
        });
    }
};
