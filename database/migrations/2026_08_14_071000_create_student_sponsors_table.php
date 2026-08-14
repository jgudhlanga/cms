<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('student_sponsors', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('student_id')->constrained()->cascadeOnDelete();
            $table->unsignedSmallInteger('calendar_year');
            $table->string('sponsor')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['student_id', 'calendar_year']);
            $table->index(['calendar_year']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('student_sponsors');
    }
};
