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
        Schema::create('department_workflow_steps', function (Blueprint $table) {
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

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('department_workflow_steps');
    }
};
