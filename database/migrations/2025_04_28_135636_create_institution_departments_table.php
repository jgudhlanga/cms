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
        Schema::create('institution_departments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->index()->constrained();
            $table->foreignId('department_id')->constrained();
            $table->string('department_code')->nullable()->unique();
            $table->text('description')->nullable();
			$table->timestamps();
			$table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('institution_departments');
    }
};
