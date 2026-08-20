<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('student_enrolments', function (Blueprint $table): void {
            if (! Schema::hasColumn('student_enrolments', 'course_syllabus_ids')) {
                $table->json('course_syllabus_ids')->nullable()->after('student_enrolment_status_id');
            }
        });
    }

    public function down(): void
    {
        Schema::table('student_enrolments', function (Blueprint $table): void {
            if (Schema::hasColumn('student_enrolments', 'course_syllabus_ids')) {
                $table->dropColumn('course_syllabus_ids');
            }
        });
    }
};
