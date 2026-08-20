<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('student_applications', function (Blueprint $table): void {
            if (! Schema::hasColumn('student_applications', 'workflow_step_id')) {
                $table->foreignId('workflow_step_id')->nullable()->after('department_application_step_id')->constrained();
            }
        });

        if (
            Schema::hasTable('department_application_steps')
            && Schema::hasColumn('student_applications', 'department_application_step_id')
        ) {
            $mappings = DB::table('department_application_steps')
                ->select('id', 'workflow_step_id')
                ->get();

            foreach ($mappings as $mapping) {
                DB::table('student_applications')
                    ->where('department_application_step_id', $mapping->id)
                    ->update(['workflow_step_id' => $mapping->workflow_step_id]);
            }
        }

        Schema::table('student_applications', function (Blueprint $table): void {
            if (Schema::hasColumn('student_applications', 'department_application_step_id')) {
                $table->dropColumn('department_application_step_id');
            }
        });

        Schema::dropIfExists('department_workflow_steps');
        Schema::dropIfExists('department_application_steps');
    }

    public function down(): void
    {
        if (! Schema::hasTable('department_application_steps')) {
            Schema::create('department_application_steps', function (Blueprint $table): void {
                $table->id();
                $table->foreignId('tenant_id')->constrained();
                $table->foreignId('institution_department_id')->constrained();
                $table->foreignId('workflow_step_id')->constrained();
                $table->integer('position')->nullable();
                $table->timestamps();
                $table->softDeletes();
            });
        }

        if (! Schema::hasTable('department_workflow_steps')) {
            Schema::create('department_workflow_steps', function (Blueprint $table): void {
                $table->id();
                $table->morphs('steppable');
                $table->json('role_ids')->nullable();
                $table->json('staff_ids')->nullable();
                $table->json('workflow_action_ids')->nullable();
                $table->text('notes')->nullable();
                $table->timestamps();
                $table->softDeletes();
            });
        }

        Schema::table('student_applications', function (Blueprint $table): void {
            if (! Schema::hasColumn('student_applications', 'department_application_step_id')) {
                $table->unsignedBigInteger('department_application_step_id')->nullable()->after('application_tracking_number');
            }
        });

        if (Schema::hasColumn('student_applications', 'workflow_step_id')) {
            Schema::table('student_applications', function (Blueprint $table): void {
                $table->dropConstrainedForeignId('workflow_step_id');
            });
        }
    }
};
