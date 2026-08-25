<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Recover from a prior failed run that created the table but not its constraints.
        Schema::dropIfExists('assessment_calendar_notification_dispatches');

        Schema::create('assessment_calendar_notification_dispatches', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->index();
            $table->foreign('tenant_id', 'acnd_tenant_fk')->references('id')->on('tenants');
            $table->foreignId('assessment_calendar_id');
            $table->foreign('assessment_calendar_id', 'acnd_calendar_fk')
                ->references('id')
                ->on('assessment_calendars')
                ->cascadeOnDelete();
            $table->string('tier');
            $table->timestamp('sent_at');
            $table->timestamps();

            $table->unique(
                ['assessment_calendar_id', 'tier'],
                'acnd_calendar_tier_unique',
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('assessment_calendar_notification_dispatches');
    }
};
