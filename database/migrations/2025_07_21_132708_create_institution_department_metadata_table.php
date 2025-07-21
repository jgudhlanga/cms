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
        Schema::create('institution_department_metadata', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('institution_department_id');
            $table->string('code')->nullable()->unique();
            $table->string('welcome_title')->nullable();
            $table->string('welcome_note')->nullable();
            $table->text('bio')->nullable();
            $table->json('what_we_offer')->nullable();
            $table->json('facilities_resources')->nullable();
            $table->string('location')->nullable();
            $table->string('email')->nullable();
            $table->string('phone_number')->nullable();
            $table->foreignId('head_of_division_id')->nullable()->constrained('staff', 'id');
            $table->foreignId('head_of_department_id')->nullable()->constrained('staff', 'id');
			$table->timestamps();
			$table->softDeletes();
            $table->foreign('institution_department_id', 'dept_meta_dept_fk')
                ->references('id')
                ->on('institution_departments')
                ->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('institution_department_metadata');
    }
};
