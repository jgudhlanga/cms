<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('course_syllabus_modules', function (Blueprint $table): void {
            $table->boolean('all_semesters')->default(false)->after('shared');
        });
    }

    public function down(): void
    {
        Schema::table('course_syllabus_modules', function (Blueprint $table): void {
            $table->dropColumn('all_semesters');
        });
    }
};
