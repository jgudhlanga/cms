<?php

declare(strict_types=1);

use App\Services\Applications\ApplicationRequirementBackfillService;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('department_level_requirements') && ! Schema::hasTable('course_requirements')) {
            return;
        }

        app(ApplicationRequirementBackfillService::class)->writeSnapshot();

        Schema::dropIfExists('course_requirements');
        Schema::dropIfExists('department_level_requirements');
    }

    public function down(): void
    {
        if (Schema::hasTable('department_level_requirements') || Schema::hasTable('course_requirements')) {
            return;
        }

        Schema::create('department_level_requirements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->index()->constrained();
            $table->foreignId('department_level_id')->index()->constrained();
            $table->boolean('is_o_level_required');
            $table->integer('required_subjects_count')->nullable();
            $table->integer('main_subjects_count')->nullable();
            $table->json('main_subject_ids')->nullable();
            $table->integer('other_subjects_count')->nullable();
            $table->boolean('only_read_write_required');
            $table->unsignedBigInteger('required_level_id')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('course_requirements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained();
            $table->foreignId('department_level_id')->constrained();
            $table->foreignId('department_course_id')->constrained();
            $table->boolean('is_o_level_required');
            $table->integer('required_subjects_count')->nullable();
            $table->integer('main_subjects_count')->nullable();
            $table->json('main_subject_ids')->nullable();
            $table->integer('other_subjects_count')->nullable();
            $table->boolean('only_read_write_required');
            $table->unsignedBigInteger('required_level_id')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        app(ApplicationRequirementBackfillService::class)->restoreLegacyFromLatestSnapshot(dryRun: false);
    }
};
