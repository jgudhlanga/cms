<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('department_assessment_calendars', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->index()->constrained();
            $table->foreignId('assessment_calendar_id')->constrained()->cascadeOnDelete();
            $table->foreignId('institution_department_id')->constrained(indexName: 'dac_inst_dept_fk')->cascadeOnDelete();
            $table->date('start_date');
            $table->date('end_date');
            $table->unsignedSmallInteger('first_notification_days_before')->nullable();
            $table->unsignedSmallInteger('second_notification_days_before')->nullable();
            $table->unsignedSmallInteger('due_notification_days_before')->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(
                ['assessment_calendar_id', 'institution_department_id'],
                'dept_assessment_calendars_calendar_department_unique',
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('department_assessment_calendars');
    }
};
