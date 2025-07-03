<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('students', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained();
            $table->foreignId('user_id')->unique()->constrained();
            $table->foreignId('title_id')->constrained();
            $table->foreignId('gender_id')->constrained();
            $table->foreignId('marital_status_id')->constrained();
            $table->foreignId('race_id')->nullable();
            $table->enum('id_type', ['zimbabwean-national-id-number', 'foreign-passport-number']);
            $table->string('id_number')->nullable();
            $table->string('passport_number')->nullable();
            $table->foreignId('country_id')->nullable();
            $table->foreignId('religion_id')->nullable();
            $table->string('study_permit_number')->nullable();
            $table->date('date_of_birth');
            $table->string('denomination')->nullable();
            $table->string('height')->nullable();
            $table->string('weight')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('students');
    }
};
