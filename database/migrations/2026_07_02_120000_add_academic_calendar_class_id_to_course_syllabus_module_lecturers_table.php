<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('course_syllabus_module_lecturers', function (Blueprint $table) {
            if (! Schema::hasColumn('course_syllabus_module_lecturers', 'academic_calendar_class_id')) {
                $table->foreignId('academic_calendar_class_id')
                    ->nullable()
                    ->after('staff_id')
                    ->constrained('academic_calendar_classes', 'id', 'csm_lecturers_class_fk')
                    ->cascadeOnDelete();
            }
        });

        Schema::table('course_syllabus_module_lecturers', function (Blueprint $table) {
            $table->index(['course_syllabus_module_id', 'staff_id'], 'csm_lecturers_module_staff_index');
        });

        Schema::table('course_syllabus_module_lecturers', function (Blueprint $table) {
            $table->dropUnique('csm_lecturers_module_staff_unique');
        });

        Schema::table('course_syllabus_module_lecturers', function (Blueprint $table) {
            $table->unique(
                ['course_syllabus_module_id', 'staff_id', 'academic_calendar_class_id'],
                'csm_lecturers_module_staff_class_unique',
            );
        });
    }

    public function down(): void
    {
        Schema::table('course_syllabus_module_lecturers', function (Blueprint $table) {
            $table->dropUnique('csm_lecturers_module_staff_class_unique');
            $table->unique(['course_syllabus_module_id', 'staff_id'], 'csm_lecturers_module_staff_unique');
            $table->dropIndex('csm_lecturers_module_staff_index');
            $table->dropForeign('csm_lecturers_class_fk');
            $table->dropColumn('academic_calendar_class_id');
        });
    }
};
