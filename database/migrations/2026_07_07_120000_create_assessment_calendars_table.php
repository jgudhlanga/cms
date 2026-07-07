<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('assessment_calendars', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->index()->constrained();
            $table->foreignId('assessment_type_id')->constrained()->cascadeOnDelete();
            $table->foreignId('academic_calendar_id')->constrained()->cascadeOnDelete();
            $table->date('start_date');
            $table->date('end_date');
            $table->string('type');
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('assessment_calendars');
    }
};
