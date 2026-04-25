<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('syllabus_course_modules') && ! Schema::hasTable('course_syllabus_modules')) {
            Schema::rename('syllabus_course_modules', 'course_syllabus_modules');
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('course_syllabus_modules') && ! Schema::hasTable('syllabus_course_modules')) {
            Schema::rename('course_syllabus_modules', 'syllabus_course_modules');
        }
    }
};
