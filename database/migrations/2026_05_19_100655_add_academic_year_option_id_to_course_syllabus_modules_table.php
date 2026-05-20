<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('course_syllabus_modules', function (Blueprint $table) {
            $table->foreignId('academic_year_option_id')
                ->nullable()
                ->after('course_syllabus_id')
                ->constrained('academic_year_options');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('course_syllabus_modules', function (Blueprint $table) {
            $table->dropConstrainedForeignId('academic_year_option_id');
        });
    }
};
