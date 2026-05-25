<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('hostel_applications', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('tenant_id')->constrained();
            $table->foreignId('student_id')->nullable()->constrained();
            $table->foreignId('student_enrolment_id')->nullable()->constrained('student_enrolments');
            $table->string('name')->nullable();
            $table->foreignId('gender_id')->constrained('genders');
            $table->string('type');
            $table->string('status')->default('pending');
            $table->string('phone_number')->nullable();
            $table->string('email_address')->nullable();
            $table->string('next_of_kin_name');
            $table->string('next_of_kin_contact');
            $table->date('check_in');
            $table->date('check_out');
            $table->json('eligibility_results')->nullable();
            $table->json('payment_verification')->nullable();
            $table->text('decline_reason')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['tenant_id', 'status', 'deleted_at']);
            $table->index(['student_id', 'status', 'deleted_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('hostel_applications');
    }
};
