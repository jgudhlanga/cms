<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('missing_marks_escalations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->index();
            $table->foreign('tenant_id', 'mme_tenant_fk')->references('id')->on('tenants');
            $table->foreignId('assessment_calendar_id')->unique();
            $table->foreign('assessment_calendar_id', 'mme_calendar_fk')
                ->references('id')
                ->on('assessment_calendars')
                ->cascadeOnDelete();
            $table->foreignId('escalated_by');
            $table->foreign('escalated_by', 'mme_escalated_by_fk')->references('id')->on('users');
            $table->text('notes')->nullable();
            $table->json('snapshot')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('missing_marks_escalations');
    }
};
