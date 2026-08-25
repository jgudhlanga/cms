<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('assessment_calendar_notification_dispatches', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->index()->constrained();
            $table->foreignId('assessment_calendar_id')->constrained()->cascadeOnDelete();
            $table->string('tier');
            $table->timestamp('sent_at');
            $table->timestamps();

            $table->unique(
                ['assessment_calendar_id', 'tier'],
                'assessment_calendar_notification_dispatches_unique',
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('assessment_calendar_notification_dispatches');
    }
};
