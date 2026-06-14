<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('course_syllabus_modules', function (Blueprint $table): void {
            $table->dropUnique('syllabus_course_modules_code_unique');
            $table->unique(['course_syllabus_id', 'code']);
        });
    }

    public function down(): void
    {
        Schema::table('course_syllabus_modules', function (Blueprint $table): void {
            $table->dropUnique(['course_syllabus_id', 'code']);
            $table->unique('code', 'syllabus_course_modules_code_unique');
        });
    }
};
