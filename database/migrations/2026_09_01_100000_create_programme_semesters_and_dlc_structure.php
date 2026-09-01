<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('department_level_courses', function (Blueprint $table): void {
            $table->unsignedTinyInteger('duration_years')->default(1)->after('department_level_id');
            $table->unsignedTinyInteger('taught_semester_count')->default(2)->after('duration_years');
            $table->boolean('includes_industrial_attachment')->default(false)->after('taught_semester_count');
            $table->unsignedTinyInteger('attachment_semester_count')->default(0)->after('includes_industrial_attachment');
        });

        Schema::create('programme_semesters', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('department_level_course_id')
                ->constrained('department_level_courses', 'id', 'prog_sem_dlc_fk')
                ->cascadeOnDelete();
            $table->unsignedTinyInteger('position');
            $table->string('name');
            $table->string('kind', 32)->default('taught');
            $table->timestamps();
            $table->softDeletes();
            $table->unique(['department_level_course_id', 'position'], 'prog_sem_dlc_position_unq');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('programme_semesters');

        Schema::table('department_level_courses', function (Blueprint $table): void {
            $table->dropColumn([
                'duration_years',
                'taught_semester_count',
                'includes_industrial_attachment',
                'attachment_semester_count',
            ]);
        });
    }
};
