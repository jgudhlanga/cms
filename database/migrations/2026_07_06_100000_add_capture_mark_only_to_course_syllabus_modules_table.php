<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('course_syllabus_modules', function (Blueprint $table): void {
            $table->boolean('capture_mark_only')->default(false)->after('all_semesters');
        });
    }

    public function down(): void
    {
        Schema::table('course_syllabus_modules', function (Blueprint $table): void {
            $table->dropColumn('capture_mark_only');
        });
    }
};
