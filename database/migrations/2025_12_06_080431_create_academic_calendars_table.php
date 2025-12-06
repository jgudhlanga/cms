<?php

use App\Enums\AcademicCalendars\AcademicCalendarTypeEnum;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('academic_calendars', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained();
            $table->string('name')->unique();
            $table->string('calendar_year');
            $table->enum('calendar_type', [AcademicCalendarTypeEnum::BLOCK->value,
                AcademicCalendarTypeEnum::MINIMESTER->value,
                AcademicCalendarTypeEnum::MODULAR->value, AcademicCalendarTypeEnum::OTHER->value,
                AcademicCalendarTypeEnum::QUADMESTER->value, AcademicCalendarTypeEnum::QUARTER->value,
                AcademicCalendarTypeEnum::SEMESTER->value, AcademicCalendarTypeEnum::TRIMESTER->value]);
            $table->date('opening_date');
            $table->date('closing_date');
            $table->string('description')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('academic_calendars');
    }
};
