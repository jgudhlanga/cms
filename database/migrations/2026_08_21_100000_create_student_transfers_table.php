<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('student_transfers', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('student_id')->constrained()->cascadeOnDelete();
            $table->foreignId('student_application_id')->constrained()->cascadeOnDelete();
            $table->string('college_name');
            $table->timestamps();
            $table->softDeletes();

            $table->unique('student_application_id');
            $table->index(['student_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('student_transfers');
    }
};
