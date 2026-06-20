<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (Schema::hasTable('academic_calandar_classes') && ! Schema::hasTable('academic_calendar_classes')) {
            Schema::rename('academic_calandar_classes', 'academic_calendar_classes');
        }

        if (Schema::hasTable('academic_calandar_class_meta_data') && ! Schema::hasTable('academic_calendar_class_meta_data')) {
            Schema::rename('academic_calandar_class_meta_data', 'academic_calendar_class_meta_data');
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('academic_calendar_classes') && ! Schema::hasTable('academic_calandar_classes')) {
            Schema::rename('academic_calendar_classes', 'academic_calandar_classes');
        }

        if (Schema::hasTable('academic_calendar_class_meta_data') && ! Schema::hasTable('academic_calandar_class_meta_data')) {
            Schema::rename('academic_calendar_class_meta_data', 'academic_calandar_class_meta_data');
        }
    }
};
