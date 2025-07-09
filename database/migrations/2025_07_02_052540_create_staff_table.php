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
        Schema::create('staff', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained();
            $table->foreignId('user_id')->constrained();
            $table->foreignId('employment_type_id')->nullable()->constrained();
            $table->string('employee_number')->nullable()->unique();
            $table->string('staff_id_number')->nullable()->unique();
            $table->foreignId('title_id')->constrained();
            $table->foreignId('gender_id')->constrained();
            $table->foreignId('marital_status_id')->constrained();
            $table->foreignId('race_id')->nullable();
            $table->foreignId('id_type_id')->nullable()->constrained();
            $table->string('id_number')->nullable();
            $table->string('passport_number')->nullable();
            $table->string('work_permit_number')->nullable();
            $table->foreignId('country_id')->nullable();
            $table->foreignId('religion_id')->nullable();
            $table->date('date_of_birth')->nullable();
            $table->string('denomination')->nullable();
            $table->string('height')->nullable();
            $table->string('weight')->nullable();
            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();
            $table->foreignId('status_id')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('staff');
    }
};
