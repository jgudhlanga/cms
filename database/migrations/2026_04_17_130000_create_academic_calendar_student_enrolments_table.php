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
        Schema::create('academic_calendar_student_enrolments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants', 'id', 'acc_cal_stu_enr_tenant_fk');
            $table->foreignId('student_enrolment_id')->constrained('student_enrolments', 'id', 'acc_cal_stu_enr_stu_enr_fk');
            $table->foreignId('academic_calendar_class_id')
                ->constrained('academic_calendar_classes', 'id', 'acc_cal_stu_enr_class_fk');
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('academic_calendar_student_enrolments');
    }
};
